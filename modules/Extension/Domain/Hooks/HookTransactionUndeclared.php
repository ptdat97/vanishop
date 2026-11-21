<?php

declare(strict_types=1);

namespace Modules\Extension\Domain\Hooks;

use LogicException;

/**
 * Hook chạy trong transaction DB nhưng hooks.php không khai báo `transaction: true` — plugin đọc khai báo sẽ tưởng được
 * gọi mạng ở đây. Chỉ ném ở chế độ strict (local/test).
 */
final class HookTransactionUndeclared extends LogicException
{
    public function __construct(string $name)
    {
        parent::__construct("Hook [{$name}] chạy trong transaction DB nhưng chưa khai báo 'transaction' => true trong hooks.php.");
    }
}
