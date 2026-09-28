<?php

declare(strict_types=1);

namespace Modules\Extension\Domain\Plugin;

use InvalidArgumentException;

final class InvalidManifest extends InvalidArgumentException
{
    public static function because(string $path, string $reason): self
    {
        return new self("Manifest [{$path}] không hợp lệ: {$reason}");
    }
}
