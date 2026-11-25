<?php

declare(strict_types=1);

namespace Modules\Storefront\Application\Builder;

use Modules\Extension\Contracts\Extensions;

/**
 * Thứ tự hiển thị của Builder: thành phần hệ thống (Core) luôn đứng trước thành phần do plugin khai báo — loại block,
 * surface, block tại slot — bất kể provider nào đăng ký trước. Trong mỗi nhóm giữ nguyên thứ tự đăng ký.
 */
final class SystemFirst
{
    /**
     * @template TKey of array-key
     * @template TItem of object
     *
     * @param  array<TKey, TItem>  $items
     * @return array<TKey, TItem>
     */
    public static function order(array $items, Extensions $extensions): array
    {
        $system = [];
        $plugin = [];
        foreach ($items as $key => $item) {
            if ($extensions->ownerOf($item) === null) {
                $system[$key] = $item;
            } else {
                $plugin[$key] = $item;
            }
        }

        return $system + $plugin;
    }
}
