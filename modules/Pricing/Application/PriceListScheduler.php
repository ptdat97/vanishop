<?php

declare(strict_types=1);

namespace Modules\Pricing\Application;

use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Pricing\Contracts\PriceListSchedule;
use Modules\Pricing\Domain\PriceListType;
use Modules\Pricing\Events\PriceChanged;
use Modules\Pricing\Persistence\Models\PriceList;

final class PriceListScheduler implements PriceListSchedule
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function schedulable(): array
    {
        return PriceList::query()->where('type', '!=', PriceListType::Base->value)->orderByDesc('priority')->orderBy('code')->get()
            ->map(fn (PriceList $list): array => ['id' => $list->id, 'code' => $list->code, 'name' => $list->name, 'type' => $list->type->value, 'status' => $list->status])->all();
    }

    public function schedule(int $priceListId, ?DateTimeInterface $startsAt, ?DateTimeInterface $endsAt, bool $active, string $reason): void
    {
        DB::transaction(function () use ($priceListId, $startsAt, $endsAt, $active, $reason): void {
            $list = PriceList::query()->whereKey($priceListId)->lockForUpdate()->first();
            if ($list === null || $list->type === PriceListType::Base) {
                throw new InvalidArgumentException("Bảng giá #{$priceListId} không tồn tại hoặc là bảng giá base.");
            }

            $list->update([
                'starts_at' => $startsAt, 'ends_at' => $endsAt, 'status' => $active ? 'active' : 'inactive',
                'lock_version' => $list->lock_version + 1,
            ]);
            $this->audit->record('pricing.price_list.scheduled', 'price_list', $list->id, ['reason' => $reason, 'active' => $active, 'starts_at' => $startsAt?->format(DATE_ATOM), 'ends_at' => $endsAt?->format(DATE_ATOM)]);
            event(new PriceChanged($list->id, $list->prices()->pluck('variant_id')->all()));
        });
    }
}
