<?php

declare(strict_types=1);

namespace Modules\Identity\Application;

use Closure;
use Modules\Identity\Domain\ScopeType;

/**
 * Lựa chọn phạm vi khi gán vai trò (Admin → Nhân viên). Identity ở vòng 0 không đọc bảng của module thương mại: module
 * sở hữu dữ liệu đăng ký nguồn, vd. Inventory đăng ký danh sách location cho `ScopeType::Location`.
 */
final class ScopeOptions
{
    /** @var array<string, Closure(): array<int, string>> */
    private array $providers = [];

    /**
     * @param  Closure(): array<int, string>  $options  id => nhãn
     */
    public function register(ScopeType $type, Closure $options): void
    {
        $this->providers[$type->value] = $options;
    }

    /**
     * @return array<string, array<int, string>> loại phạm vi có id => (id => nhãn); `owner` không cần id nên không có
     */
    public function all(): array
    {
        return array_map(fn (Closure $options): array => $options(), $this->providers);
    }

    public function exists(ScopeType $type, ?int $id): bool
    {
        if ($type === ScopeType::Owner) {
            return $id === null;
        }

        return $id !== null && isset($this->providers[$type->value]) && array_key_exists($id, ($this->providers[$type->value])());
    }
}
