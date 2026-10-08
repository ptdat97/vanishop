<?php

declare(strict_types=1);

namespace App\Observability;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;
use ZipArchive;

/**
 * Diễn tập khôi phục backup (go-live gate, operations §8): lấy bản backup mới nhất trên disk backup → giải nén dump DB →
 * nạp vào database TẠM tách biệt (`<db>_drill_<thời gian>`, không đụng DB đang chạy) → đối chiếu với DB thật → xoá DB tạm
 * → ghi kết quả vào `backup_drills` làm bằng chứng.
 *
 * Đạt khi: nạp dump không lỗi; có bảng `migrations` và mọi migration trong bản khôi phục đều có ở DB thật; mọi bảng
 * nghiệp vụ chính có mặt. Số dòng bản khôi phục lớn hơn DB thật được ghi là bất thường (dữ liệu bị xoá sau thời điểm
 * backup?) nhưng không làm hỏng diễn tập. Chỉ hỗ trợ MySQL/MariaDB.
 */
final class BackupRestoreDrill
{
    /** Bảng nghiệp vụ đối chiếu số dòng. */
    public const TABLES = ['orders', 'order_lines', 'payments', 'refunds', 'customers', 'styles', 'variants', 'prices', 'stock_levels', 'stock_movements', 'promotions', 'integration_events', 'plugins', 'settings'];

    /**
     * @return array{status: 'ok'|'failed', backup: string, details: array<string, mixed>}
     */
    public function run(?string $disk = null, bool $keep = false): array
    {
        $started = microtime(true);
        $disk ??= (string) (config('backup.backup.destination.disks')[0] ?? 'local');
        $connection = (string) config('database.default');
        $base = (array) config("database.connections.{$connection}");
        $drillDb = substr($base['database'].'_drill_'.now()->format('YmdHis'), 0, 64);
        $workDir = storage_path('app/backup-drill-'.bin2hex(random_bytes(4)));
        $details = ['drill_database' => $drillDb, 'tables' => [], 'anomalies' => []];
        $backup = '';
        $backupAt = null;
        $created = false;

        try {
            if (! in_array($base['driver'] ?? '', ['mysql', 'mariadb'], true)) {
                throw new RuntimeException('Diễn tập khôi phục chỉ hỗ trợ MySQL/MariaDB.');
            }
            [$backup, $backupAt] = $this->latestBackup($disk);
            $dump = $this->extractDump($disk, $backup, $workDir);

            config(['database.connections.backup_drill' => [...$base, 'database' => $drillDb], 'database.connections.backup_drill_admin' => $base]);
            // DDL trên connection riêng: CREATE/DROP DATABASE tự commit transaction đang mở của connection chính.
            DB::connection('backup_drill_admin')->statement("CREATE DATABASE `{$drillDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $created = true;
            $this->import($base, $drillDb, $dump);

            $this->compare($connection, $details);
            $status = in_array(true, array_column($details['anomalies'], 'fatal'), true) ? 'failed' : 'ok';
        } catch (Throwable $exception) {
            report($exception);
            $status = 'failed';
            $details['error'] = $exception->getMessage();
        } finally {
            if ($created && ! $keep) {
                rescue(fn () => DB::connection('backup_drill_admin')->statement("DROP DATABASE IF EXISTS `{$drillDb}`"), report: true);
                $details['dropped'] = true;
            }
            DB::purge('backup_drill');
            DB::purge('backup_drill_admin');
            File::deleteDirectory($workDir);
        }

        DB::table('backup_drills')->insert([
            'disk' => $disk, 'backup_path' => $backup, 'backup_created_at' => $backupAt, 'status' => $status,
            'duration_ms' => (int) round((microtime(true) - $started) * 1000), 'details' => json_encode($details, JSON_UNESCAPED_UNICODE), 'created_at' => now(),
        ]);

        return ['status' => $status, 'backup' => $backup, 'details' => $details];
    }

    /**
     * @return array{0: string, 1: Carbon}
     */
    private function latestBackup(string $disk): array
    {
        $storage = Storage::disk($disk);
        $files = collect($storage->allFiles((string) config('backup.backup.name')))->filter(fn (string $file): bool => str_ends_with($file, '.zip'));
        $latest = $files->sortByDesc(fn (string $file): int => $storage->lastModified($file))->first()
            ?? throw new RuntimeException("Không có bản backup nào trên disk [{$disk}].");

        return [$latest, Carbon::createFromTimestamp($storage->lastModified($latest))];
    }

    private function extractDump(string $disk, string $backup, string $workDir): string
    {
        File::ensureDirectoryExists($workDir);
        $zipPath = "{$workDir}/backup.zip";
        File::put($zipPath, (string) Storage::disk($disk)->get($backup));

        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException('File backup không mở được (zip hỏng?).');
        }
        $password = config('backup.backup.password');
        if (is_string($password) && $password !== '') {
            $zip->setPassword($password);
        }
        $entry = null;
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $name = (string) $zip->getNameIndex($index);
            if (str_starts_with($name, 'db-dumps/') && (str_ends_with($name, '.sql') || str_ends_with($name, '.sql.gz'))) {
                $entry = $name;
            }
        }
        if ($entry === null || ! $zip->extractTo($workDir, $entry)) {
            $zip->close();
            throw new RuntimeException('Backup không có bản dump DB (db-dumps/*.sql) hoặc không giải nén được (sai mật khẩu?).');
        }
        $zip->close();

        $dump = "{$workDir}/{$entry}";
        if (str_ends_with($dump, '.gz')) {
            File::put(substr($dump, 0, -3), (string) gzdecode((string) File::get($dump)));
            $dump = substr($dump, 0, -3);
        }

        return $dump;
    }

    /**
     * @param  array<string, mixed>  $base
     */
    private function import(array $base, string $database, string $dump): void
    {
        $binaryDir = (string) ($base['dump']['dump_binary_path'] ?? '');
        $binary = $binaryDir === '' ? 'mysql' : rtrim($binaryDir, '/').'/mysql';
        $process = new Process(
            [$binary, '--host='.$base['host'], '--port='.$base['port'], '--user='.$base['username'], '--default-character-set=utf8mb4', $database],
            null,
            ['MYSQL_PWD' => (string) ($base['password'] ?? '')],
            fopen($dump, 'r'),
            3600,
        );
        $process->run();
        if (! $process->isSuccessful()) {
            throw new RuntimeException('Nạp dump lỗi: '.trim($process->getErrorOutput()));
        }
    }

    /**
     * @param  array<string, mixed>  $details
     */
    private function compare(string $connection, array &$details): void
    {
        $restored = DB::connection('backup_drill');
        $restoredTables = array_map(fn ($row): string => (string) array_values((array) $row)[0], $restored->select('SHOW TABLES'));

        if (! in_array('migrations', $restoredTables, true)) {
            $details['anomalies'][] = ['fatal' => true, 'message' => 'Bản khôi phục không có bảng migrations.'];

            return;
        }
        $unknown = array_diff($restored->table('migrations')->pluck('migration')->all(), DB::connection($connection)->table('migrations')->pluck('migration')->all());
        if ($unknown !== []) {
            $details['anomalies'][] = ['fatal' => true, 'message' => 'Bản khôi phục có migration mà DB thật không có: '.implode(', ', array_slice($unknown, 0, 5))];
        }
        $details['migrations'] = $restored->table('migrations')->count();

        foreach (self::TABLES as $table) {
            if (! in_array($table, $restoredTables, true)) {
                $details['anomalies'][] = ['fatal' => true, 'message' => "Bản khôi phục thiếu bảng {$table}."];

                continue;
            }
            $restoredCount = $restored->table($table)->count();
            $liveCount = DB::connection($connection)->table($table)->count();
            $details['tables'][$table] = ['restored' => $restoredCount, 'live' => $liveCount];
            if ($restoredCount > $liveCount) {
                $details['anomalies'][] = ['fatal' => false, 'message' => "{$table}: bản khôi phục {$restoredCount} dòng > DB thật {$liveCount} (dữ liệu bị xoá sau thời điểm backup?)."];
            }
        }
    }
}
