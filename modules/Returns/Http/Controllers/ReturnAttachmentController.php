<?php

declare(strict_types=1);

namespace Modules\Returns\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Modules\Returns\Persistence\Models\ReturnAttachment;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Ảnh đính kèm đổi trả (§3.13-3): chỉ qua link có chữ ký (middleware `signed`) phát cho khách chủ đơn và nhân viên.
 */
final class ReturnAttachmentController
{
    public function __invoke(ReturnAttachment $attachment): StreamedResponse
    {
        return Storage::disk($attachment->disk)->response($attachment->path, null, [
            'Content-Type' => $attachment->mime_type,
            'Cache-Control' => 'private, max-age=1800',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
