<?php

declare(strict_types=1);

namespace Modules\Customer\Application;

use Modules\Customer\Contracts\CustomerDirectory;
use Modules\Customer\Contracts\Data\CustomerSummary;
use Modules\Customer\Domain\CustomerStatus;
use Modules\Customer\Persistence\Models\Customer;

final class CustomerDirectoryService implements CustomerDirectory
{
    public function __construct(private readonly CustomerQueries $queries) {}

    public function search(string $query, int $limit = 20): array
    {
        if (trim($query) === '') {
            return [];
        }

        return array_map($this->summary(...), $this->queries->search($query, CustomerStatus::Active->value, max(1, min(50, $limit)))->items());
    }

    public function summaries(array $customerIds): array
    {
        return Customer::query()->whereKey(array_values(array_unique(array_map('intval', $customerIds))))
            ->get(['id', 'full_name', 'phone', 'email'])->mapWithKeys(fn (Customer $customer): array => [$customer->id => $this->summary($customer)])->all();
    }

    public function customerIds(?int $groupId, ?string $tag, int $afterId = 0, int $limit = 500): array
    {
        return Customer::query()
            ->where('status', CustomerStatus::Active)
            ->where('id', '>', $afterId)
            ->when($groupId !== null, fn ($query) => $query->where('customer_group_id', $groupId))
            ->when($tag !== null && $tag !== '', fn ($query) => $query->whereIn('id', fn ($sub) => $sub->select('customer_id')->from('customer_tags')->where('tag', $tag)))
            ->orderBy('id')->limit(max(1, min(CustomerDirectory::MAX_BATCH, $limit)))
            ->pluck('id')->map(fn (mixed $id): int => (int) $id)->all();
    }

    private function summary(Customer $customer): CustomerSummary
    {
        return new CustomerSummary($customer->id, $customer->full_name, $customer->phone, $customer->email);
    }
}
