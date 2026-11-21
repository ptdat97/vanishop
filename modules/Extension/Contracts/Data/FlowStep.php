<?php

declare(strict_types=1);

namespace Modules\Extension\Contracts\Data;

use Modules\Extension\Contracts\Flows;

/**
 * Một bước của luồng nghiệp vụ ({@see Flows}): bước của Core, hook (listener do
 * `vani:flows` điền), hoặc implementation của extension point (calculator, validator…).
 */
final readonly class FlowStep
{
    public function __construct(
        /** Khoá dịch (bước Core), tên hook, hoặc mã implementation. */
        public string $name,
        /** Bước chạy trong transaction DB: listener/implementation chỉ ghi DB của mình, không gọi mạng. */
        public bool $transaction,
        public ?string $hook = null,
        /** Plugin sở hữu implementation; null = Core. */
        public ?string $owner = null,
        public ?int $priority = null,
    ) {}

    public static function core(string $label, bool $transaction): self
    {
        return new self($label, $transaction);
    }

    public static function hook(string $name, bool $transaction): self
    {
        return new self($name, $transaction, hook: $name);
    }

    public static function implementation(string $code, ?string $owner, bool $transaction, ?int $priority = null): self
    {
        return new self($code, $transaction, owner: $owner, priority: $priority);
    }
}
