<?php

declare(strict_types=1);

namespace App\Observability;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Extension\Contracts\Extensions;
use Modules\Shared\Contracts\AlertChannel;
use Modules\Shared\Contracts\Data\Alert;
use Modules\Shared\Contracts\Metrics;
use Throwable;

/**
 * Đánh giá AlertRules rồi báo qua mọi AlertChannel đang bật, theo trạng thái lưu ở `alert_states`:
 * mới phát sinh → báo ngay; còn → nhắc lại theo `repeat_minutes` của mức độ; hết → báo "đã ổn". Không gửi lặp mỗi phút.
 *
 * DB không truy cập được (không đánh giá/lưu được trạng thái) → báo một cảnh báo khẩn `database`, chống lặp bằng cache.
 */
final class AlertManager
{
    private const DATABASE_DOWN_KEY = 'vani:alerts:database-down';

    public function __construct(
        private readonly AlertRules $rules,
        private readonly Extensions $extensions,
        private readonly Metrics $metrics,
    ) {}

    /**
     * @return list<Alert> cảnh báo đã gửi lần chạy này
     */
    public function run(bool $dryRun = false): array
    {
        try {
            DB::select('select 1');
        } catch (Throwable $exception) {
            report($exception);

            return $this->databaseDown($dryRun);
        }

        $sent = [];
        $now = Carbon::now();
        $states = DB::table('alert_states')->get()->keyBy('key');
        foreach ($this->rules->evaluate() as $key => $incident) {
            $state = $states[$key] ?? null;
            $firing = $state !== null && $state->status === Alert::FIRING;

            if ($incident === null) {
                if ($firing) {
                    $sent[] = $alert = new Alert($key, $state->severity, Alert::RESOLVED, $state->title, 'Đã trở lại bình thường.', Carbon::parse($state->fired_at)->toDateTimeImmutable());
                    $dryRun || DB::table('alert_states')->where('key', $key)->update(['status' => 'resolved', 'resolved_at' => $now, 'updated_at' => $now]);
                    $dryRun || $this->deliver($alert);
                }

                continue;
            }

            $since = $firing ? Carbon::parse($state->fired_at) : $now;
            $repeat = (int) (config("vanishop.alerts.repeat_minutes.{$incident['severity']}") ?? 60);
            $due = ! $firing || $state->last_notified_at === null || Carbon::parse($state->last_notified_at)->addMinutes($repeat)->lte($now);

            $alert = new Alert($key, $incident['severity'], $firing ? Alert::REMINDER : Alert::FIRING, $incident['title'], $incident['detail'], $since->toDateTimeImmutable());
            if (! $dryRun) {
                DB::table('alert_states')->updateOrInsert(['key' => $key], [
                    'severity' => $incident['severity'], 'status' => Alert::FIRING, 'title' => $incident['title'], 'detail' => $incident['detail'],
                    'fired_at' => $since, 'resolved_at' => null, 'updated_at' => $now,
                    ...($firing ? [] : ['created_at' => $state->created_at ?? $now, 'notify_count' => 0]),
                    ...($due ? ['last_notified_at' => $now, 'notify_count' => ($firing ? (int) $state->notify_count : 0) + 1] : []),
                ]);
                if (! $firing) {
                    $this->metrics->increment('alerts.fired', 1, $incident['severity']);
                }
            }
            if ($due) {
                $sent[] = $alert;
                $dryRun || $this->deliver($alert);
            }
        }

        return $sent;
    }

    /**
     * @return list<Alert>
     */
    private function databaseDown(bool $dryRun): array
    {
        $alert = new Alert('database', Alert::CRITICAL, Alert::FIRING, 'Không kết nối được cơ sở dữ liệu', 'Website không đặt hàng được. Kiểm tra MySQL (dịch vụ, kết nối mạng, dung lượng đĩa).', now()->toDateTimeImmutable());
        try {
            $due = $dryRun || Cache::add(self::DATABASE_DOWN_KEY, 1, now()->addMinutes((int) config('vanishop.alerts.repeat_minutes.critical', 30)));
        } catch (Throwable) {
            $due = true; // cache cũng hỏng: vẫn báo (scheduler chạy mỗi phút, chấp nhận lặp hơn là im lặng)
        }
        if (! $due) {
            return [];
        }
        $dryRun || $this->deliver($alert);

        return [$alert];
    }

    private function deliver(Alert $alert): void
    {
        Log::log($alert->severity === Alert::CRITICAL && $alert->state !== Alert::RESOLVED ? 'critical' : 'warning', $alert->subject(), ['alert' => $alert->key, 'detail' => $alert->detail]);

        foreach ($this->extensions->implementations(AlertChannel::TAG, AlertChannel::class) as $channel) {
            $this->extensions->call($channel, fn () => $channel->send($alert), null, "alert.send.{$channel->code()}");
        }
    }
}
