<?php

declare(strict_types=1);

namespace Modules\Extension\Application;

use Illuminate\Database\Migrations\Migrator;
use Throwable;

/**
 * Migration (Core, module, plugin) chưa chạy trên DB hiện tại — code mới mà DB cũ thì trang lỗi 500 (vd. thiếu bảng).
 * Dùng cho /health, `vani:security:check` và "Việc cần làm" của Tổng quan.
 */
final class PendingMigrations
{
    public function __construct(private readonly Migrator $migrator) {}

    /**
     * @return list<string> tên migration chưa chạy
     */
    public function names(): array
    {
        try {
            if (! $this->migrator->repositoryExists()) {
                return ['migrations'];
            }
            $files = $this->migrator->getMigrationFiles([database_path('migrations'), ...$this->migrator->paths()]);
            $ran = $this->migrator->getRepository()->getRan();

            return array_values(array_diff(array_keys($files), $ran));
        } catch (Throwable) {
            return [];
        }
    }
}
