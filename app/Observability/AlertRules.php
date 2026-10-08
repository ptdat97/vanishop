<?php

declare(strict_types=1);

namespace App\Observability;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Laravel\Horizon\WaitTimeCalculator;
use Modules\Extension\Application\Plugins\PluginHealth;
use Modules\Extension\Application\Plugins\RequiredExtensions;
use Modules\Extension\Domain\Plugin\PluginStatus;
use Modules\Extension\Persistence\Models\PluginRecord;
use Modules\Shared\Contracts\Data\Alert;
use Throwable;

/**
 * Điều kiện cảnh báo (observability §7) — composition root, chỉ đọc số liệu của các module như MetricsSnapshot.
 *
 * Mỗi điều kiện trả `null` (ổn) hoặc mô tả sự cố. Điều kiện lỗi khi đánh giá thì bị bỏ khỏi kết quả (không coi là ổn,
 * để cảnh báo đang mở không bị đóng nhầm) và ghi log.
 *
 * Chưa có (cần hạ tầng/dữ liệu chưa có): tỷ lệ checkout lỗi theo 5 phút (chỉ có counter Pulse), replica lag, deadlock,
 * slow query. Scheduler chết thì lệnh này cũng không chạy — dùng giám sát ngoài gọi GET /health.
 */
final class AlertRules
{
    public function __construct(
        private readonly RequiredExtensions $required,
        private readonly PluginHealth $pluginHealth,
    ) {}

    /**
     * @return array<string, array{severity: string, title: string, detail: string}|null> key => sự cố (null = ổn)
     */
    public function evaluate(): array
    {
        $results = [];
        foreach ($this->rules() as $key => $rule) {
            try {
                $results[$key] = $rule();
            } catch (Throwable $exception) {
                report($exception);
                Log::warning('Không đánh giá được điều kiện cảnh báo.', ['alert' => $key]);
            }
        }

        return $results;
    }

    /**
     * @return array<string, Closure(): (array{severity: string, title: string, detail: string}|null)>
     */
    private function rules(): array
    {
        $config = (array) config('vanishop.alerts');

        return [
            'extensions.required_missing' => function (): ?array {
                $enabled = PluginRecord::query()->where('status', PluginStatus::Enabled)->pluck('id')->all();
                $missing = $this->required->missing($enabled);

                return $missing === [] ? null : $this->incident(Alert::CRITICAL, 'Thiếu extension bắt buộc (thanh toán/giao hàng/thuế…)', 'Thiếu: '.implode(', ', $missing).'. Kiểm tra php artisan vani:plugin:doctor và vani:plugin:cache.');
            },
            'integration.order_event_dead' => function (): ?array {
                $dead = DB::table('integration_outbox')->where('status', 'dead')->where('message_type', 'like', 'order.%')->count();

                return $dead === 0 ? null : $this->incident(Alert::CRITICAL, 'Message order.* vào dead', "{$dead} message đơn hàng không gửi được tới đối tác/ERP. Admin → Tích hợp để xem lỗi và replay.");
            },
            'payments.failure_rate' => function () use ($config): ?array {
                $rows = DB::table('payments')->where('updated_at', '>=', now()->subHour())
                    ->whereNotIn('gateway_code', (array) $config['offline_gateways'])
                    ->whereIn('status', ['paid', 'failed', 'refunded', 'partially_refunded'])
                    ->selectRaw("count(*) as total, sum(case when status = 'failed' then 1 else 0 end) as failed")->first();
                $total = (int) ($rows->total ?? 0);
                $failed = (int) ($rows->failed ?? 0);
                if ($total < (int) $config['payment_min_sample'] || $failed / $total <= (float) $config['payment_failure_rate']) {
                    return null;
                }

                return $this->incident(Alert::CRITICAL, 'Tỷ lệ thanh toán online thất bại cao', sprintf('%d/%d khoản thất bại trong 60 phút (%.0f%%). Kiểm tra cổng thanh toán; cân nhắc tạm ẩn cổng, ưu tiên COD.', $failed, $total, 100 * $failed / $total));
            },
            'queue.wait' => function () use ($config): ?array {
                if (config('queue.default') !== 'redis' || ! class_exists(WaitTimeCalculator::class)) {
                    return null;
                }
                $slow = array_filter(app(WaitTimeCalculator::class)->calculate(), fn ($seconds): bool => $seconds > (int) $config['queue_wait_seconds']);
                if ($slow === []) {
                    return null;
                }
                $list = implode(', ', array_map(fn (string $queue, $seconds): string => "{$queue} ~".(int) $seconds.'s', array_keys($slow), $slow));

                return $this->incident(Alert::CRITICAL, 'Queue chờ quá lâu', "{$list}. Kiểm tra Horizon (worker còn chạy? đã horizon:terminate sau deploy?).");
            },
            'integration.outbox_backlog' => function () use ($config): ?array {
                $backlog = DB::table('integration_outbox')->whereIn('status', ['pending', 'processing'])->count();
                $oldest = DB::table('integration_outbox')->where('status', 'pending')
                    ->where(fn ($query) => $query->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', now()))->min('created_at');
                $lagging = $oldest !== null && now()->diffInMinutes($oldest, true) > (int) $config['outbox_lag_minutes'];
                if ($backlog <= (int) $config['outbox_backlog'] && ! $lagging) {
                    return null;
                }

                return $this->incident(Alert::HIGH, 'Outbox tích hợp tồn đọng', "Tồn {$backlog} message".($lagging ? ', message cũ nhất chờ gửi từ '.$oldest.' (UTC)' : '').'. Kiểm tra lịch vani:integration:dispatch và đối tác nhận.');
            },
            'integration.dead' => function (): ?array {
                $outbox = DB::table('integration_outbox')->where('status', 'dead')->where('message_type', 'not like', 'order.%')->count();
                $inbox = DB::table('integration_inbox')->where('status', 'dead')->count();

                return $outbox + $inbox === 0 ? null : $this->incident(Alert::HIGH, 'Message tích hợp vào dead', "Outbox {$outbox}, inbox {$inbox}. Admin → Tích hợp để xem lỗi và replay.");
            },
            'plugins.failed' => function (): ?array {
                $failed = PluginRecord::query()->where('status', PluginStatus::Failed)->pluck('id')->all();

                return $failed === [] ? null : $this->incident(Alert::HIGH, 'Plugin lỗi (failed)', 'Plugin: '.implode(', ', $failed).'. Xem Admin → Plugin; php artisan vani:plugin:doctor.');
            },
            'plugins.health' => function (): ?array {
                $errors = array_filter($this->pluginHealth->last(), fn (array $result): bool => $result['status'] === 'error');
                if ($errors === []) {
                    return null;
                }
                $list = implode('; ', array_map(fn (string $plugin, array $result): string => "{$plugin}: {$result['message']}", array_keys($errors), $errors));

                return $this->incident(Alert::HIGH, 'Kiểm tra sức khoẻ plugin báo lỗi', $list);
            },
            'jobs.failed' => function (): ?array {
                if (! Schema::hasTable('failed_jobs')) {
                    return null;
                }
                $failed = DB::table('failed_jobs')->where('failed_at', '>=', now()->subHour())->count();

                return $failed === 0 ? null : $this->incident(Alert::HIGH, 'Job queue thất bại', "{$failed} job thất bại trong 60 phút. Xem Horizon → Failed jobs.");
            },
            'reconciliation.open' => function (): ?array {
                $inventory = DB::table('inventory_reconciliation_lines')->whereNull('resolved_at')->count();
                $payments = Schema::hasTable('payment_reconciliation_lines') ? DB::table('payment_reconciliation_lines')->whereNull('resolved_at')->count() : 0;

                return $inventory + $payments === 0 ? null : $this->incident(Alert::NORMAL, 'Có chênh lệch đối soát chưa xử lý', "Tồn kho {$inventory} dòng, thanh toán {$payments} dòng. Admin → Tồn kho → Đối soát / Thanh toán.");
            },
        ];
    }

    /**
     * @return array{severity: string, title: string, detail: string}
     */
    private function incident(string $severity, string $title, string $detail): array
    {
        return ['severity' => $severity, 'title' => $title, 'detail' => $detail];
    }
}
