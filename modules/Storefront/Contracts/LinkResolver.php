<?php

declare(strict_types=1);

namespace Modules\Storefront\Contracts;

use Modules\Storefront\Contracts\Data\LinkTarget;
use Modules\Storefront\Contracts\Data\ResolvedLink;

/**
 * Service contract: phân giải link có kiểu thành URL + nhãn theo ngôn ngữ, qua `LinkType` đã đăng ký. Nhãn ghi đè của
 * `LinkTarget` thắng nhãn mặc định của đối tượng.
 */
interface LinkResolver
{
    public function resolve(LinkTarget $target, string $locale): ResolvedLink;

    /**
     * Phân giải nhiều link một lần (một truy vấn mỗi loại), giữ thứ tự và khoá đầu vào.
     *
     * @param  array<array-key, LinkTarget>  $targets
     * @return array<array-key, ResolvedLink>
     */
    public function resolveMany(array $targets, string $locale): array;

    /** Loại link còn hoạt động và giá trị hợp lệ về dạng. */
    public function accepts(LinkTarget $target): bool;

    /**
     * Loại link đang có (cho Admin).
     *
     * @return list<array{type: string, label: string, searchable: bool, options: list<array{value: string, label: string}>}>
     */
    public function types(): array;

    /**
     * @return list<array{value: string, label: string}>
     */
    public function search(string $type, string $query, string $locale, int $limit = 20): array;
}
