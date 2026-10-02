<?php

declare(strict_types=1);

namespace Modules\Cart\Tests\Feature\Fixtures;

use Modules\Cart\Contracts\CartLineOption;
use Modules\Cart\Contracts\InvalidCartLineOption;

/** Tuỳ chọn khắc chữ giả lập (in hoa, tối đa 10 ký tự). */
final class EngravingOption implements CartLineOption
{
    public function normalize(int $variantId, array $values): array
    {
        $text = strtoupper(trim((string) ($values['text'] ?? '')));
        if (strlen($text) > 10) {
            throw new InvalidCartLineOption('Chữ khắc tối đa 10 ký tự.');
        }

        return $text === '' ? [] : ['text' => $text];
    }
}
