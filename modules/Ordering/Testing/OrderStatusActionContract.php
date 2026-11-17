<?php

declare(strict_types=1);

namespace Modules\Ordering\Testing;

use Closure;
use Modules\Ordering\Contracts\Data\OrderData;
use Modules\Ordering\Contracts\OrderStatusAction;

/**
 * Contract test cho OrderStatusAction. Kiểm tra: mã ổn định; với đơn mẫu, describe trả null hoặc nút có nhãn + mã quyền +
 * tone hợp lệ; xác định. Không gọi perform (đổi trạng thái — plugin tự test theo nghiệp vụ).
 */
final class OrderStatusActionContract
{
    /**
     * @param  Closure(): OrderStatusAction  $action
     * @param  Closure(): OrderData  $order
     */
    public static function define(string $label, Closure $action, Closure $order): void
    {
        describe("OrderStatusAction contract: {$label}", function () use ($action, $order): void {
            it('mã; nút hợp lệ hoặc null; xác định', function () use ($action, $order): void {
                expect($action()->code())->toMatch('/^[a-z0-9._-]+$/');
                $data = $order();
                $view = $action()->describe($data);
                expect($action()->describe($data))->toEqual($view);
                if ($view !== null) {
                    expect($view->label)->not->toBe('')->and($view->permission)->not->toBe('')
                        ->and($view->tone)->toBeIn(['primary', 'secondary', 'danger']);
                }
            });
        });
    }
}
