<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Observability\BackupRestoreDrill;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Diễn tập khôi phục backup (operations §8, go-live gate): nạp bản backup mới nhất vào DB tạm, đối chiếu, xoá DB tạm,
 * ghi kết quả vào `backup_drills`. Mã thoát 1 nếu không đạt. Chạy mỗi quý và sau mỗi lần đổi cấu hình backup.
 */
final class BackupDrillCommand extends Command
{
    protected $signature = 'vani:backup:drill
        {--fresh : Chạy backup:run --only-db trước để diễn tập với bản mới nhất}
        {--with-files : Cùng --fresh: backup đầy đủ (DB + storage/app) để diễn tập cả phần file}
        {--disk= : Disk backup (mặc định: disk đầu tiên trong VANI_BACKUP_DISKS)}
        {--keep : Giữ lại DB tạm để kiểm tra thủ công (nhớ tự DROP)}';

    protected $description = 'Diễn tập khôi phục: nạp bản backup mới nhất vào DB tạm và đối chiếu với DB thật';

    public function handle(BackupRestoreDrill $drill): int
    {
        $archive = null;
        if ($this->option('fresh')) {
            $filename = now()->format('Y-m-d-H-i-s').'-drill-'.Str::ulid().'.zip';
            $backupOptions = ['--disable-notifications' => true, '--filename' => $filename, '--only-db' => ! $this->option('with-files')];
            if ($this->call('backup:run', $backupOptions) !== self::SUCCESS) {
                $this->error('backup:run lỗi — không diễn tập.');

                return self::FAILURE;
            }
            $archive = config('backup.backup.name').'/'.config('backup.backup.destination.filename_prefix', '').$filename;
        }

        $result = $drill->run($this->option('disk') ?: null, (bool) $this->option('keep'), $archive);
        $details = $result['details'];
        $this->line("Bản backup: {$result['backup']}");
        if (($details['tables'] ?? []) !== []) {
            $this->table(['Bảng', 'Bản khôi phục', 'DB thật'], array_map(fn (string $table, array $count): array => [$table, $count['restored'], $count['live']], array_keys($details['tables']), $details['tables']));
        }
        if (isset($details['files'])) {
            $this->line("File storage/app: {$details['files']['archived']} trong backup".(isset($details['files']['restored']) ? ", giải nén được {$details['files']['restored']}" : '').", hiện có {$details['files']['live']}.");
        }
        foreach ($details['anomalies'] as $anomaly) {
            $anomaly['fatal'] ? $this->error("✗ {$anomaly['message']}") : $this->warn("! {$anomaly['message']}");
        }
        if (isset($details['error'])) {
            $this->error("✗ {$details['error']}");
        }
        if ($this->option('keep') && ! isset($details['dropped'])) {
            $this->warn("Giữ DB tạm {$details['drill_database']} — DROP DATABASE sau khi kiểm tra.");
        }

        if ($result['status'] !== 'ok') {
            $this->error('Diễn tập khôi phục KHÔNG ĐẠT.');

            return self::FAILURE;
        }
        $this->info('Diễn tập khôi phục đạt.');

        return self::SUCCESS;
    }
}
