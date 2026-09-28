<?php

declare(strict_types=1);

namespace Modules\Pricing\Application;

use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Channel\Contracts\ChannelDirectory;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Pricing\Events\PriceChanged;
use Modules\Pricing\Persistence\Models\Price;
use Modules\Pricing\Persistence\Models\PriceHistory;
use Modules\Pricing\Persistence\Models\PriceList;
use Modules\Shared\Context\CurrentContext;

/**
 * Bảng giá: tạo/sửa, gán kênh, nhập giá hàng loạt (ghi lịch sử giá + audit + event).
 */
final class PriceListService
{
    public const MAX_AMOUNT = 1_000_000_000_000;

    public function __construct(
        private readonly AuditLogger $audit,
        private readonly ChannelDirectory $channels,
        private readonly VariantDirectory $variants,
        private readonly CurrentContext $context,
    ) {}

    /**
     * @param  array{code: string, name: string, type: string, priority: int, starts_at: ?string, ends_at: ?string, status: string}  $data
     * @param  list<int>  $channelIds
     */
    public function save(int $brandId, array $data, array $channelIds, ?PriceList $list = null, ?int $expectedLockVersion = null): PriceList
    {
        if ($data['starts_at'] !== null && $data['ends_at'] !== null && strtotime($data['ends_at']) <= strtotime($data['starts_at'])) {
            throw ValidationException::withMessages(['ends_at' => __('pricing::messages.window_invalid')]);
        }

        $allowed = array_column($this->channels->forBrand($brandId), 'id');
        if (array_diff($channelIds, $allowed) !== []) {
            throw ValidationException::withMessages(['channel_ids' => __('pricing::messages.channel_not_for_brand')]);
        }

        return DB::transaction(function () use ($brandId, $data, $channelIds, $list, $expectedLockVersion): PriceList {
            if ($list !== null) {
                $updated = PriceList::query()->whereKey($list->id)->where('lock_version', $expectedLockVersion)->increment('lock_version');
                if ($updated === 0) {
                    throw ValidationException::withMessages(['lock_version' => __('pricing::messages.stale')]);
                }
            }

            $list ??= new PriceList(['brand_id' => $brandId, 'currency_code' => 'VND']);
            $list->fill($data)->save();

            DB::table('channel_price_lists')->where('price_list_id', $list->id)->delete();
            foreach (array_unique($channelIds) as $channelId) {
                DB::table('channel_price_lists')->insert(['channel_id' => $channelId, 'price_list_id' => $list->id, 'customer_group_id' => null]);
            }

            $this->audit->record($list->wasRecentlyCreated ? 'pricing.price_list.created' : 'pricing.price_list.updated', 'price_list', $list->id, ['code' => $list->code, 'channels' => $channelIds]);
            event(new PriceChanged($list->id, $brandId, $list->prices()->pluck('variant_id')->all()));

            return $list;
        });
    }

    /**
     * Nhập giá cho nhiều variant. amount null = gỡ giá khỏi bảng giá.
     *
     * @param  list<array{variant_id: int, amount: int|null, compare_at_amount: int|null}>  $rows
     */
    public function setPrices(PriceList $list, array $rows): int
    {
        $known = $this->variants->find(array_column($rows, 'variant_id'));
        foreach ($rows as $index => $row) {
            if (! isset($known[$row['variant_id']]) || $known[$row['variant_id']]->brandId !== $list->brand_id) {
                throw ValidationException::withMessages(["prices.{$index}.variant_id" => __('pricing::messages.variant_not_in_brand')]);
            }
            if ($row['amount'] !== null && $row['compare_at_amount'] !== null && $row['compare_at_amount'] <= $row['amount']) {
                throw ValidationException::withMessages(["prices.{$index}.compare_at_amount" => __('pricing::messages.compare_at_invalid')]);
            }
        }

        return DB::transaction(function () use ($list, $rows): int {
            $existing = Price::query()->where('price_list_id', $list->id)->where('min_qty', 1)
                ->whereIn('variant_id', array_column($rows, 'variant_id'))->lockForUpdate()->get()->keyBy('variant_id');
            $changed = [];

            foreach ($rows as $row) {
                $current = $existing->get($row['variant_id']);
                $oldAmount = $current?->amount;
                $oldCompare = $current?->compare_at_amount;

                if ($oldAmount === $row['amount'] && $oldCompare === ($row['amount'] === null ? null : $row['compare_at_amount'])) {
                    continue;
                }

                if ($row['amount'] === null) {
                    $current?->delete();
                } else {
                    Price::query()->updateOrCreate(
                        ['price_list_id' => $list->id, 'variant_id' => $row['variant_id'], 'min_qty' => 1],
                        ['amount' => $row['amount'], 'compare_at_amount' => $row['compare_at_amount']],
                    );
                }

                $this->history($list->id, $row['variant_id'], $oldAmount, $row['amount'], $oldCompare, $row['amount'] === null ? null : $row['compare_at_amount']);
                $changed[] = $row['variant_id'];
            }

            if ($changed !== []) {
                $this->audit->record('pricing.prices.updated', 'price_list', $list->id, ['variants' => count($changed)]);
                event(new PriceChanged($list->id, $list->brand_id, $changed));
            }

            return count($changed);
        });
    }

    public function delete(PriceList $list): void
    {
        DB::transaction(function () use ($list): void {
            $variantIds = $list->prices()->pluck('variant_id')->all();
            $list->delete();
            $this->audit->record('pricing.price_list.deleted', 'price_list', $list->id, ['code' => $list->code]);
            event(new PriceChanged($list->id, $list->brand_id, $variantIds));
        });
    }

    private function history(int $listId, int $variantId, ?int $oldAmount, ?int $newAmount, ?int $oldCompare, ?int $newCompare): void
    {
        $actor = $this->context->has() ? $this->context->actor() : null;

        PriceHistory::query()->create([
            'price_list_id' => $listId,
            'variant_id' => $variantId,
            'old_amount' => $oldAmount,
            'new_amount' => $newAmount,
            'old_compare_at_amount' => $oldCompare,
            'new_compare_at_amount' => $newCompare,
            'actor_type' => $actor?->type->value,
            'actor_id' => $actor?->id,
            'correlation_id' => Context::get('correlation_id'),
            'created_at' => now(),
        ]);
    }
}
