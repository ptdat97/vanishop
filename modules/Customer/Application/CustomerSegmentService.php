<?php

declare(strict_types=1);

namespace Modules\Customer\Application;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Customer\Contracts\CustomerSegments;
use Modules\Customer\Contracts\Data\CustomerSegment;
use Modules\Customer\Persistence\Models\Customer;
use Modules\Customer\Persistence\Models\CustomerGroup;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Pricing\Contracts\CustomerGroupDirectory;

/**
 * Phân khúc khách (roadmap Phase 8): một khách tối đa một nhóm — Pricing dùng để chọn bảng giá thành viên
 * (CustomerGroupDirectory) — và nhiều tag tự do (lọc, nhắm khuyến mãi). Tag chuẩn hoá thành slug (vd. "Khách sỉ HN" →
 * `khach-si-hn`).
 */
final class CustomerSegmentService implements CustomerGroupDirectory, CustomerSegments
{
    public const MAX_TAGS = 20;

    /** @var array<int, ?int> theo request: giá được tính nhiều lần cho cùng khách */
    private array $groupCache = [];

    public function __construct(private readonly AuditLogger $audit) {}

    public function groupOf(int $customerId): ?int
    {
        if (! array_key_exists($customerId, $this->groupCache)) {
            $group = Customer::query()->whereKey($customerId)->value('customer_group_id');
            $this->groupCache[$customerId] = $group === null ? null : (int) $group;
        }

        return $this->groupCache[$customerId];
    }

    public function groups(): array
    {
        return CustomerGroup::query()->orderBy('position')->orderBy('name')->get(['id', 'code', 'name'])
            ->map(fn (CustomerGroup $group): array => ['id' => $group->id, 'code' => $group->code, 'name' => $group->name])->all();
    }

    public function segmentOf(int $customerId): CustomerSegment
    {
        $groupId = $this->groupOf($customerId);
        $group = $groupId === null ? null : CustomerGroup::query()->find($groupId);

        return new CustomerSegment($group?->id, $group?->code, $group?->name, $this->tagsOf($customerId));
    }

    /**
     * @return list<string>
     */
    public function tagsOf(int $customerId): array
    {
        return DB::table('customer_tags')->where('customer_id', $customerId)->orderBy('tag')->pluck('tag')->all();
    }

    /**
     * @param  array{code: string, name: string, description: ?string, position: int}  $data
     */
    public function saveGroup(array $data, ?CustomerGroup $group = null): CustomerGroup
    {
        $group ??= new CustomerGroup;
        $group->fill($data)->save();
        $this->audit->record($group->wasRecentlyCreated ? 'customer.group.created' : 'customer.group.updated', 'customer_group', $group->id, ['code' => $group->code]);

        return $group;
    }

    /**
     * Chỉ xoá nhóm không còn khách (bảng giá gắn nhóm đã xoá sẽ không áp cho ai).
     */
    public function deleteGroup(CustomerGroup $group): void
    {
        if ($group->customers()->exists()) {
            throw ValidationException::withMessages(['group' => __('customer::messages.group_in_use')]);
        }
        $group->delete();
        $this->audit->record('customer.group.deleted', 'customer_group', $group->id, ['code' => $group->code]);
    }

    /**
     * Gán nhóm + thay toàn bộ tag của khách.
     *
     * @param  list<string>  $tags
     */
    public function assign(Customer $customer, ?int $groupId, array $tags): void
    {
        if ($groupId !== null && ! CustomerGroup::query()->whereKey($groupId)->exists()) {
            throw ValidationException::withMessages(['customer_group_id' => __('customer::messages.group_not_found')]);
        }
        $tags = self::normalizeTags($tags);
        if (count($tags) > self::MAX_TAGS) {
            throw ValidationException::withMessages(['tags' => __('customer::messages.too_many_tags', ['max' => self::MAX_TAGS])]);
        }

        DB::transaction(function () use ($customer, $groupId, $tags): void {
            $before = ['group' => $customer->customer_group_id, 'tags' => $this->tagsOf($customer->id)];
            Customer::query()->whereKey($customer->id)->update(['customer_group_id' => $groupId, 'updated_at' => now()]);
            DB::table('customer_tags')->where('customer_id', $customer->id)->whereNotIn('tag', $tags)->delete();
            foreach (array_diff($tags, $before['tags']) as $tag) {
                DB::table('customer_tags')->insert(['customer_id' => $customer->id, 'tag' => $tag, 'created_at' => now()]);
            }
            $this->audit->record('customer.segment_changed', 'customer', $customer->id, ['from' => $before, 'to' => ['group' => $groupId, 'tags' => $tags]]);
        });
        unset($this->groupCache[$customer->id]);
    }

    /**
     * @param  list<string>  $tags
     * @return list<string>
     */
    public static function normalizeTags(array $tags): array
    {
        return array_values(array_unique(array_filter(array_map(fn (string $tag): string => Str::limit(Str::slug($tag), 40, ''), $tags))));
    }
}
