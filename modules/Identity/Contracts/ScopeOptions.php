<?php

declare(strict_types=1);

namespace Modules\Identity\Contracts;

use Closure;
use Modules\Identity\Domain\ScopeType;

/**
 * Service contract: lựa chọn phạm vi khi gán vai trò (vd. từng location) do module sở hữu phạm vi đăng ký — C1.
 */
interface ScopeOptions
{
    /**
     * @param  Closure(): array<int, string>  $options  id => nhãn
     */
    public function register(ScopeType $type, Closure $options): void;

    /**
     * @return array<string, array<int, string>> loại phạm vi có id => (id => nhãn); `owner` không cần id nên không có
     */
    public function all(): array;

    public function exists(ScopeType $type, ?int $id): bool;
}
