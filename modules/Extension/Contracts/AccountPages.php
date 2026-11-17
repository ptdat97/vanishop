<?php

declare(strict_types=1);

namespace Modules\Extension\Contracts;

/**
 * Service contract: trang tài khoản khách do plugin thêm (menu "Tài khoản" của storefront) — C1.
 */
interface AccountPages
{
    public function add(string $plugin, string $key, string $label, string $route, int $order = 500): void;

    /**
     * @return list<array{key: string, label: string, url: string}>
     */
    public function active(): array;
}
