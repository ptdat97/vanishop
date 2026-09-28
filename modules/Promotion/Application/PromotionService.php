<?php

declare(strict_types=1);

namespace Modules\Promotion\Application;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Promotion\Persistence\Models\Promotion;
use Modules\Promotion\Persistence\Models\Voucher;

/**
 * Khuyến mãi và voucher trong Admin (brand workspace).
 */
final class PromotionService
{
    private const VOUCHER_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public function __construct(
        private readonly AuditLogger $audit,
        private readonly PromotionRegistry $registry,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(int $brandId, array $data, ?Promotion $promotion = null, ?int $expectedLockVersion = null): Promotion
    {
        if ($data['starts_at'] !== null && $data['ends_at'] !== null && strtotime((string) $data['ends_at']) <= strtotime((string) $data['starts_at'])) {
            throw ValidationException::withMessages(['ends_at' => __('pricing::messages.window_invalid')]);
        }

        $action = $this->registry->action((string) $data['action_type']);
        $errors = $action === null ? ['Loại giảm giá không hỗ trợ.'] : $action->validateConfig($data['action_config']);
        if ($errors !== []) {
            throw ValidationException::withMessages(['action_config' => $errors]);
        }

        return DB::transaction(function () use ($brandId, $data, $promotion, $expectedLockVersion): Promotion {
            if ($promotion !== null) {
                $updated = Promotion::query()->whereKey($promotion->id)->where('lock_version', $expectedLockVersion)->increment('lock_version');
                if ($updated === 0) {
                    throw ValidationException::withMessages(['lock_version' => __('promotion::messages.stale')]);
                }
            }

            $promotion ??= new Promotion(['brand_id' => $brandId]);
            $promotion->fill($data)->save();
            $this->audit->record($promotion->wasRecentlyCreated ? 'promotion.created' : 'promotion.updated', 'promotion', $promotion->id, ['name' => $promotion->name, 'action' => $promotion->action_type, 'config' => $promotion->action_config]);

            return $promotion;
        });
    }

    public function delete(Promotion $promotion): void
    {
        if (DB::table('promotion_usages')->where('promotion_id', $promotion->id)->exists()) {
            throw ValidationException::withMessages(['promotion' => __('promotion::messages.in_use')]);
        }

        DB::transaction(function () use ($promotion): void {
            $promotion->delete();
            $this->audit->record('promotion.deleted', 'promotion', $promotion->id, ['name' => $promotion->name]);
        });
    }

    /**
     * Tạo một mã cụ thể (code) hoặc sinh hàng loạt (prefix + count mã ngẫu nhiên).
     *
     * @return int số mã đã tạo
     */
    public function createVouchers(Promotion $promotion, ?string $code, ?string $prefix, int $count, ?int $usageLimit, ?string $expiresAt): int
    {
        $codes = $code !== null && $code !== ''
            ? [Voucher::normalize($code)]
            : $this->generateCodes(Voucher::normalize((string) $prefix), $count);

        if ($codes !== [] && Voucher::query()->whereIn('code', $codes)->exists()) {
            throw ValidationException::withMessages(['code' => 'Mã đã tồn tại.']);
        }

        DB::transaction(function () use ($promotion, $codes, $usageLimit, $expiresAt): void {
            foreach ($codes as $voucherCode) {
                $promotion->vouchers()->create(['code' => $voucherCode, 'usage_limit' => $usageLimit, 'expires_at' => $expiresAt, 'status' => 'active']);
            }
            $this->audit->record('promotion.vouchers_created', 'promotion', $promotion->id, ['count' => count($codes), 'sample' => array_slice($codes, 0, 3)]);
        });

        return count($codes);
    }

    public function setVoucherStatus(Voucher $voucher, string $status): void
    {
        $voucher->update(['status' => $status]);
        $this->audit->record('promotion.voucher_status', 'voucher', $voucher->id, ['code' => $voucher->code, 'status' => $status]);
    }

    /**
     * @return list<string>
     */
    private function generateCodes(string $prefix, int $count): array
    {
        $codes = [];
        while (count($codes) < $count) {
            $random = '';
            for ($i = 0; $i < 8; $i++) {
                $random .= self::VOUCHER_ALPHABET[random_int(0, strlen(self::VOUCHER_ALPHABET) - 1)];
            }
            $codes[$prefix.$random] = true;
        }

        $codes = array_keys($codes);
        $taken = Voucher::query()->whereIn('code', $codes)->pluck('code')->all();

        return $taken === [] ? $codes : [...array_values(array_diff($codes, $taken)), ...$this->generateCodes($prefix, count($taken))];
    }
}
