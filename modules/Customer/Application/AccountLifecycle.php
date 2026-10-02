<?php

declare(strict_types=1);

namespace Modules\Customer\Application;

use Illuminate\Support\Facades\DB;
use Modules\Customer\Contracts\CustomerRejected;
use Modules\Customer\Domain\CustomerStatus;
use Modules\Customer\Events\CustomerAnonymized;
use Modules\Customer\Events\CustomerMerged;
use Modules\Customer\Persistence\Models\Customer;
use Modules\Customer\Persistence\Models\CustomerAddress;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Ordering\Contracts\CustomerOrders;
use Modules\Ordering\Contracts\OrderWriter;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

/**
 * Hợp nhất khách trùng, ẩn danh hoá (quyền xoá của chủ thể dữ liệu) và xuất dữ liệu cá nhân.
 */
final class AccountLifecycle
{
    public function __construct(
        private readonly OrderWriter $orders,
        private readonly CustomerOrders $customerOrders,
        private readonly ConsentService $consents,
        private readonly AuthService $auth,
        private readonly CustomerStats $stats,
        private readonly AuditLogger $audit,
        private readonly CurrentContext $context,
    ) {}

    /**
     * Chuyển đơn, địa chỉ, consent của khách nguồn sang khách đích; nguồn thành `merged` (giải phóng SĐT/email).
     * Không mất đơn: đơn chuyển trong cùng transaction.
     */
    public function merge(int $sourceId, int $targetId): int
    {
        if ($sourceId === $targetId) {
            throw CustomerRejected::mergeInvalid('Không thể hợp nhất khách với chính nó.');
        }

        $moved = DB::transaction(function () use ($sourceId, $targetId): int {
            $locked = Customer::query()->whereIn('id', [$sourceId, $targetId])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $source = $locked[$sourceId] ?? throw CustomerRejected::notFound();
            $target = $locked[$targetId] ?? throw CustomerRejected::notFound();
            if (! $source->isActive() || ! $target->isActive()) {
                throw CustomerRejected::mergeInvalid('Chỉ hợp nhất được hai khách đang hoạt động.');
            }

            $moved = $this->orders->reassignCustomer($sourceId, $targetId);
            CustomerAddress::query()->where('customer_id', $sourceId)->update(['customer_id' => $targetId, 'is_default' => false]);

            foreach ($this->consents->all($sourceId) as $consent) {
                if ($consent['granted'] && ! $this->consents->allows($targetId, $consent['channel'], $consent['purpose'])) {
                    $this->consents->set($targetId, $consent['channel'], $consent['purpose'], true, "merge:{$source->public_id}");
                }
            }

            $source->update(['status' => CustomerStatus::Merged, 'merged_into_id' => $targetId]);
            $target->update(array_filter([
                'email' => $target->email ?? $source->email,
                'full_name' => $target->full_name ?? $source->full_name,
                'registered_at' => $target->registered_at ?? $source->registered_at,
            ], fn (mixed $value): bool => $value !== null));
            $this->auth->revokeAll($sourceId);
            $this->stats->recompute($sourceId);
            $this->stats->recompute($targetId);

            $this->audit->record('customer.merged', 'customer', $targetId, ['source' => $source->public_id, 'orders' => $moved]);
            event(new CustomerMerged($sourceId, $targetId, $moved));

            return $moved;
        });

        return $moved;
    }

    /**
     * Ẩn danh hoá: xoá thông tin cá nhân trên hồ sơ, địa chỉ, phiên đăng nhập; rút mọi consent. Đơn hàng giữ
     * snapshot theo nghĩa vụ kế toán/thuế (security §quyền chủ thể dữ liệu).
     */
    public function anonymize(int $customerId, string $source): void
    {
        DB::transaction(function () use ($customerId, $source): void {
            $customer = Customer::query()->whereKey($customerId)->lockForUpdate()->first() ?? throw CustomerRejected::notFound();
            if ($customer->status === CustomerStatus::Anonymized) {
                return;
            }

            $this->consents->revokeAll($customerId, "anonymize:{$source}");
            CustomerAddress::query()->where('customer_id', $customerId)->delete();
            $this->auth->revokeAll($customerId);
            $customer->update([
                'status' => CustomerStatus::Anonymized, 'phone' => null, 'email' => null, 'full_name' => null, 'birth_date' => null,
                'gender' => null, 'password' => null, 'meta' => null,
            ]);

            $this->audit->record('customer.anonymized', 'customer', $customerId, ['source' => $source]);
            event(new CustomerAnonymized($customerId));
        });
    }

    /**
     * Dữ liệu cá nhân của khách (quyền truy cập/di chuyển dữ liệu).
     *
     * @return array<string, mixed>
     */
    public function export(int $customerId): array
    {
        $customer = Customer::query()->findOrFail($customerId);
        $orders = $this->context->runAs(ContextScope::system('customer data export'), fn (): array => $this->customerOrders->ofCustomer($customerId, 1, 1000)['data']);

        return [
            'exported_at' => now()->toIso8601String(),
            'profile' => CustomerService::toData($customer)->toArray() + [
                'registered_at' => $customer->registered_at?->toIso8601String(), 'created_at' => $customer->created_at?->toIso8601String(),
            ],
            'addresses' => app(AddressBook::class)->all($customerId),
            'consents' => $this->consents->all($customerId),
            'consent_history' => DB::table('customer_consent_events')->where('customer_id', $customerId)->orderBy('id')
                ->get(['channel', 'purpose', 'action', 'source', 'created_at'])->map(fn (object $row): array => (array) $row)->all(),
            'orders' => array_map(fn ($order): array => [
                'number' => $order->number, 'placed_at' => $order->placedAt, 'status' => $order->customerStatus['label'],
                'total' => $order->amounts['total'], 'currency' => $order->currencyCode,
                'shipping_address' => $order->shippingAddress, 'lines' => $order->lines,
            ], $orders),
        ];
    }
}
