<?php

declare(strict_types=1);

namespace Modules\Checkout\Application;

use Modules\Checkout\Application\Calculators\PromotionCalculator;
use Modules\Checkout\Application\Calculators\ShippingCalculator;
use Modules\Checkout\Contracts\CheckoutValidator;
use Modules\Checkout\Contracts\TotalsCalculator;
use Modules\Extension\Contracts\Data\FlowStep;
use Modules\Extension\Contracts\Extensions;

/**
 * Các bước của xem trước checkout và đặt hàng ({@see CheckoutService}) cho `vani:flows`. Thứ tự tính tổng lấy từ chính
 * {@see TotalsPipeline::steps()}; thứ tự hook được test đối chiếu với lần đặt hàng thật (CheckoutFlowsTest).
 */
final class CheckoutFlows
{
    public const QUOTE = 'checkout.quote';

    public const PLACE_ORDER = 'checkout.place_order';

    private const KEY = 'checkout::texts.flows.';

    public function __construct(
        private readonly TotalsPipeline $pipeline,
        private readonly Extensions $extensions,
    ) {}

    /**
     * @return list<FlowStep>
     */
    public function quote(): array
    {
        return [
            FlowStep::core(self::KEY.'load_cart', false),
            FlowStep::hook('vani.service.pricing.prices', false),
            FlowStep::hook('vani.checkout.context', false),
            ...$this->totals(false),
            FlowStep::hook('vani.checkout.payment_methods', false),
            FlowStep::hook('vani.service.checkout.quote', false),
        ];
    }

    /**
     * @return list<FlowStep>
     */
    public function placeOrder(): array
    {
        $validators = array_map(
            fn (CheckoutValidator $validator): FlowStep => FlowStep::implementation('CheckoutValidator '.$validator->code(), $this->extensions->ownerOf($validator), true),
            array_values(array_filter($this->extensions->tagged(CheckoutValidator::TAG), fn (object $validator): bool => $validator instanceof CheckoutValidator)),
        );

        return [
            FlowStep::core(self::KEY.'guest_checkout', false),
            FlowStep::core(self::KEY.'claim_idempotency', false),
            FlowStep::core(self::KEY.'resolve_customer', false),
            FlowStep::core(self::KEY.'lock_cart', true),
            FlowStep::hook('vani.service.pricing.prices', true),
            FlowStep::hook('vani.checkout.context', true),
            ...$this->totals(true),
            FlowStep::hook('vani.checkout.before_validate', true),
            ...$validators,
            FlowStep::hook('vani.checkout.payment_methods', true),
            FlowStep::hook('vani.checkout.after_validate', true),
            FlowStep::core(self::KEY.'check_expected_total', true),
            FlowStep::core(self::KEY.'reserve_stock', true),
            FlowStep::hook('vani.order.before_create', true),
            FlowStep::core(self::KEY.'create_order', true),
            FlowStep::core(self::KEY.'create_payment', true),
            FlowStep::core(self::KEY.'record_promotions', true),
            FlowStep::hook('vani.order.after_create', true),
            FlowStep::core(self::KEY.'close_cart', true),
            FlowStep::hook('vani.event.ordering.order_placed', false),
        ];
    }

    /**
     * @return list<FlowStep>
     */
    private function totals(bool $transaction): array
    {
        $steps = [];
        foreach ($this->pipeline->steps() as $step) {
            if (! $step instanceof TotalsCalculator) {
                $steps[] = FlowStep::hook($step, $transaction);

                continue;
            }
            $steps[] = FlowStep::implementation('TotalsCalculator '.$step->code(), $this->extensions->ownerOf($step), $transaction, $step->priority());
            // Hook gọi bên trong calculator của Core.
            $nested = match (true) {
                $step instanceof PromotionCalculator => 'vani.service.promotion.result',
                $step instanceof ShippingCalculator => 'vani.checkout.shipping_options',
                default => null,
            };
            if ($nested !== null) {
                $steps[] = FlowStep::hook($nested, $transaction);
            }
        }

        return $steps;
    }
}
