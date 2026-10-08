<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Cổng cấu hình trước go-live / mỗi lần deploy production (docs/18-operations/production.md §5): kiểm tra cấu hình theo
 * chuẩn production. Lỗi → mã thoát 1 (chặn deploy); cảnh báo → vẫn 0. Chạy sau `php artisan optimize`.
 */
final class SecurityCheckCommand extends Command
{
    protected $signature = 'vani:security:check';

    protected $description = 'Kiểm tra cấu hình bảo mật/vận hành theo chuẩn production (mã thoát 1 nếu có lỗi)';

    public function handle(): int
    {
        $errors = 0;
        foreach ($this->checks() as [$level, $message, $failed]) {
            if (! $failed) {
                continue;
            }
            $errors += $level === 'error' ? 1 : 0;
            $level === 'error' ? $this->error("✗ {$message}") : $this->warn("! {$message}");
        }

        if ($errors === 0) {
            $this->info('Không có lỗi cấu hình (cảnh báo nếu có ở trên).');

            return self::SUCCESS;
        }
        $this->line("{$errors} lỗi — sửa .env rồi chạy lại php artisan optimize.");

        return self::FAILURE;
    }

    /**
     * @return list<array{0: 'error'|'warning', 1: string, 2: bool}> mức, mô tả, có vi phạm không
     */
    private function checks(): array
    {
        $https = str_starts_with((string) config('app.url'), 'https://');

        return [
            ['error', 'APP_ENV phải là production', ! app()->isProduction()],
            ['error', 'APP_DEBUG phải tắt (lộ stack trace, biến môi trường)', (bool) config('app.debug')],
            ['error', 'APP_KEY chưa đặt', (string) config('app.key') === ''],
            ['error', 'APP_URL phải là https://', ! $https],
            ['error', 'SESSION_SECURE_COOKIE phải bật (cookie chỉ gửi qua HTTPS)', config('session.secure') !== true],
            ['error', 'VANI_ADMIN_PATH đang là mặc định "admin" — đặt đường dẫn khó đoán (ADR-020)', trim((string) config('vanishop.admin.path'), '/') === 'admin'],
            ['error', 'MAIL_MAILER đang là log/array — email đơn hàng và cảnh báo sẽ không gửi', in_array((string) config('mail.default'), ['log', 'array'], true)],
            ['error', 'VANI_PLUGINS_SAFE_MODE đang bật — không plugin nào được nạp (mất thanh toán/giao hàng)', (bool) config('vanishop.plugins.safe_mode')],
            ['error', 'VANI_OTP_LOG_SENDER đang bật — mã OTP bị ghi ra log', (bool) config('vanishop.customer.otp.log_sender')],
            ['warning', 'VANI_ADMIN_IP_ALLOWLIST trống — mọi IP vào được trang đăng nhập Admin', (array) config('vanishop.admin.ip_allowlist') === []],
            ['warning', 'VANI_HEALTH_TOKEN trống — /health không trả chi tiết cho giám sát', (string) config('vanishop.health.token') === ''],
            ['warning', 'VANI_TRUSTED_PROXIES trống — sau LB/CDN, IP khách và HTTPS sẽ sai', trim((string) config('vanishop.trusted_proxies')) === ''],
            ['warning', 'QUEUE_CONNECTION nên là redis (Horizon) ở production', in_array((string) config('queue.default'), ['sync', 'database'], true)],
            ['warning', 'CACHE_STORE nên là redis (dùng chung giữa server, khoá scheduler onOneServer)', in_array((string) config('cache.default'), ['file', 'array', 'database'], true)],
            ['warning', 'SESSION_DRIVER nên là redis (nhiều web node)', in_array((string) config('session.driver'), ['file', 'array'], true)],
            ['warning', 'Backup chỉ lưu trên disk local — đặt VANI_BACKUP_DISKS=s3_backup (bucket riêng, khác vùng)', array_diff((array) config('backup.backup.destination.disks'), ['local']) === []],
            ['warning', 'VANI_ALERT_EMAILS trống — cảnh báo tự động chỉ ghi log', (array) config('vanishop.alerts.mail_to') === []],
            ['warning', 'VANI_HOOKS_STRICT đang bật — lỗi kiểu dữ liệu của hook sẽ ném exception thay vì ghi log', (bool) config('vanishop.hooks.strict')],
        ];
    }
}
