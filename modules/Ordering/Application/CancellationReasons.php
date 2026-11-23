<?php

declare(strict_types=1);

namespace Modules\Ordering\Application;

use Modules\Extension\Facades\Hook;
use Modules\Tenancy\Contracts\Settings;

final class CancellationReasons
{
    public const SETTING = 'orders.cancellation_reasons';

    public function __construct(private readonly Settings $settings) {}

    /** @return array<string, string> */
    public function customerOptions(): array
    {
        return (array) __('ordering::labels.customer_cancellation_reasons');
    }

    /** @return array<string, string> */
    public function options(): array
    {
        $options = (array) __('ordering::labels.cancellation_reasons') + $this->customerOptions();
        $configured = (string) $this->settings->get('core', self::SETTING, '');
        foreach (preg_split('/\R/u', $configured) ?: [] as $line) {
            [$code, $label] = array_map('trim', array_pad(explode('|', $line, 2), 2, ''));
            if ($this->valid($code, $label)) {
                $options[$code] = $label;
            }
        }
        $proposed = Hook::filter('vani.order.cancellation_reasons', [], $options);
        foreach ((array) $proposed as $code => $label) {
            if ($this->valid($code, $label)) {
                $options[$code] ??= trim($label);
            }
        }

        return $options;
    }

    private function valid(mixed $code, mixed $label): bool
    {
        return is_string($code) && preg_match('/^[a-z][a-z0-9_.-]{0,63}$/D', $code) === 1
            && is_string($label) && trim($label) !== '' && mb_strlen($label) <= 120;
    }
}
