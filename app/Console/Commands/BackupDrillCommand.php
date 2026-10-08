<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Observability\BackupRestoreDrill;
use Illuminate\Console\Command;

/**
 * Diễn tập khôi phục backup (operations §8, go-live gate): nạp bản backup mới nhất vào DB tạm, đối chiếu, xoá DB tạm,
 * ghi kết quả vào `backup_drills`. Mã thoát 1 nếu không đạt. Chạy mỗi quý và sau mỗi lần đổi cấu hình backup.
 */
final class BackupDrillCommand extends Command
{
    protected $signature = 'vani:backup:drill
        {--fresh : Chạy backup:run --only-db trước để diễn tập với bản mới nhất}
        {--disk= : Disk backup (mặc định: disk đầu tiên trong VANI_BACKUP_DISKS)}
        {--keep : Giữ lại DB tạm để kiểm tra thủ công (nhớ tự DROP)}';

    protected $description = 'Diễn tập khôi phục: nạp bản backup mới nhất vào DB tạm và đối chiếu với DB thật';

    public function handle(BackupRestoreDrill $drill): int
    {
        if ($this->option('fresh') && $this->call('backup:run', ['--only-db' => true, '--disable-notifications' => true]) !== self::SUCCESS) {
            $this->error('backup:run lỗi — không diễn tập.');

            return self::FAILURE;
        }

        $result = $drill->run($this->option('disk') ?: null, (bool) $this->option('keep'));
        $details = $result['details'];
        $this->line("Bản backup: {$result['backup']}");
        if (($details['tables'] ?? []) !== []) {
            $this->table(['Bảng', 'Bản khôi phục', 'DB thật'], array_map(fn (string $table, array $count): array => [$table, $count['restored'], $count['live']], array_keys($details['tables']), $details['tables']));
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
