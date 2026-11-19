<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Listeners;

use Illuminate\Support\Facades\Gate;
use Modules\Customer\Contracts\CustomerNotes;
use Modules\Customer\Persistence\Models\Customer;
use Modules\Ordering\Contracts\Data\OrderDetail;
use Modules\Ordering\Contracts\OrderReader;

/**
 * Thẻ "Ghi chú CSKH" trên trang đơn Admin (slot vani.admin.order.sidebar, EP v3 X6): ghi chú gần nhất của khách (nhân
 * viên và plugin) + thao tác thêm ghi chú. Đơn của khách vãng lai (chưa có hồ sơ) không có thẻ.
 */
final class OrderCustomerNotesPanel
{
    public function __construct(private readonly OrderReader $orders, private readonly CustomerNotes $notes) {}

    /**
     * @return array<string, mixed>|null
     */
    public function __invoke(OrderDetail $order): ?array
    {
        $customerId = $this->orders->find($order->id)?->customerId;
        if ($customerId === null || ! Gate::allows('customers.view')) {
            return null;
        }
        $publicId = (string) Customer::query()->whereKey($customerId)->value('public_id');
        $notes = $this->notes->latest($customerId, 3);

        return [
            'title' => __('customer::texts.order_customer_notes_panel.customer_service_notes'),
            'rows' => $notes === [] ? [['label' => __('customer::texts.order_customer_notes_panel.no_notes_yet'), 'value' => '']] : array_map(fn (array $note): array => ['label' => (string) $note['at'], 'value' => $note['note'].($note['source'] === 'staff' ? '' : " ({$note['source']})")], $notes),
            'link' => ['label' => __('customer::texts.order_customer_notes_panel.customer_profile'), 'url' => route('admin.customers.show', $publicId)],
            'actions' => Gate::allows('customers.segment') ? [[
                'key' => 'customer-note', 'label' => __('customer::texts.order_customer_notes_panel.add_customer_service_note'), 'url' => route('admin.customers.notes.store', $publicId), 'tone' => 'secondary',
                'fields' => [['key' => 'note', 'label' => __('customer::texts.order_customer_notes_panel.note_customer_pick_up_after'), 'required' => true]],
            ]] : [],
        ];
    }
}
