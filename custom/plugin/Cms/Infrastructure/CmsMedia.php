<?php

declare(strict_types=1);

namespace Plugin\Cms\Infrastructure;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Ảnh của nội dung (ảnh bìa, ảnh trong bài) — cùng disk với ảnh catalog (`vanishop.media.disk`), thư mục `cms/`.
 */
final class CmsMedia
{
    public const MIMES = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    public const MAX_KB = 5120;

    public function store(UploadedFile $file): string
    {
        $extension = strtolower($file->guessExtension() ?? $file->getClientOriginalExtension());

        return (string) $file->storeAs('cms/'.now()->format('Y/m'), Str::lower((string) Str::ulid()).'.'.$extension, ['disk' => $this->disk()]);
    }

    public function url(?string $path): ?string
    {
        return $path === null || $path === '' ? null : Storage::disk($this->disk())->url($path);
    }

    private function disk(): string
    {
        return (string) config('vanishop.media.disk', 'public');
    }
}
