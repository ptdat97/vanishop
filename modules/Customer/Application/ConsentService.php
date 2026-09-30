<?php

declare(strict_types=1);

namespace Modules\Customer\Application;

use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Modules\Customer\Events\ConsentChanged;

/**
 * Consent theo brand × kênh × mục đích. Cấp phải có nguồn + thời điểm; rút có hiệu lực ngay. Mọi thay đổi
 * ghi ledger `customer_consent_events` (append-only).
 */
final class ConsentService
{
    public const CHANNELS = ['email', 'sms', 'zns'];

    public const PURPOSES = ['marketing'];

    /**
     * @return list<array{brand_id: int, channel: string, purpose: string, granted: bool, granted_at: ?string, revoked_at: ?string, source: string}>
     */
    public function all(int $customerId): array
    {
        return DB::table('customer_consents')->where('customer_id', $customerId)->orderBy('brand_id')->orderBy('channel')->get()
            ->map(fn (object $row): array => [
                'brand_id' => (int) $row->brand_id, 'channel' => (string) $row->channel, 'purpose' => (string) $row->purpose,
                'granted' => $row->revoked_at === null && $row->granted_at !== null,
                'granted_at' => $row->granted_at, 'revoked_at' => $row->revoked_at, 'source' => (string) $row->source,
            ])->all();
    }

    public function allows(int $customerId, int $brandId, string $channel, string $purpose): bool
    {
        return DB::table('customer_consents')->where(['customer_id' => $customerId, 'brand_id' => $brandId, 'channel' => $channel, 'purpose' => $purpose])
            ->whereNotNull('granted_at')->whereNull('revoked_at')->exists();
    }

    /**
     * @return bool true = trạng thái thay đổi
     */
    public function set(int $customerId, int $brandId, string $channel, string $purpose, bool $granted, string $source, ?string $ip = null): bool
    {
        return DB::transaction(function () use ($customerId, $brandId, $channel, $purpose, $granted, $source, $ip): bool {
            $key = ['customer_id' => $customerId, 'brand_id' => $brandId, 'channel' => $channel, 'purpose' => $purpose];
            $row = DB::table('customer_consents')->where($key)->lockForUpdate()->first();
            $current = $row !== null && $row->granted_at !== null && $row->revoked_at === null;
            if ($current === $granted) {
                return false;
            }

            $values = $granted
                ? ['granted_at' => now(), 'revoked_at' => null, 'source' => $source, 'updated_at' => now()]
                : ['revoked_at' => now(), 'source' => $source, 'updated_at' => now()];
            if ($row === null) {
                DB::table('customer_consents')->insert([...$key, ...$values, 'created_at' => now()]);
            } else {
                DB::table('customer_consents')->where('id', $row->id)->update($values);
            }

            DB::table('customer_consent_events')->insert([
                ...$key, 'action' => $granted ? 'granted' : 'revoked', 'source' => $source, 'ip' => $ip,
                'correlation_id' => Context::get('correlation_id'), 'created_at' => now(),
            ]);
            event(new ConsentChanged($customerId, $brandId, $channel, $purpose, $granted));

            return true;
        });
    }

    public function revokeAll(int $customerId, string $source): void
    {
        foreach ($this->all($customerId) as $consent) {
            if ($consent['granted']) {
                $this->set($customerId, $consent['brand_id'], $consent['channel'], $consent['purpose'], false, $source);
            }
        }
    }
}
