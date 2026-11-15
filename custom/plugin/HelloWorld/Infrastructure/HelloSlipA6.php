<?php

declare(strict_types=1);

namespace Plugin\HelloWorld\Infrastructure;

use Modules\Ordering\Contracts\PackingSlipTemplate;

/**
 * Mẫu phiếu giao A6 (EP v3 X3 — tham chiếu cho `PackingSlipTemplate`).
 */
final class HelloSlipA6 implements PackingSlipTemplate
{
    public function code(): string
    {
        return 'hello-a6';
    }

    public function label(): string
    {
        return 'A6 máy in nhiệt (Hello)';
    }

    public function view(): string
    {
        return 'vani-hello-world-admin::slip-a6';
    }
}
