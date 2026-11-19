<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Gửi thêm ảnh cho yêu cầu đổi trả đang mở — Storefront API và Blade dùng chung (ADR-044 §2); luật ảnh như lúc tạo
 * yêu cầu (`ReturnRequestForm::photoRules`), ở đây bắt buộc có ảnh.
 */
final class ReturnPhotosRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ReturnRequestForm::photoRules(required: true);
    }
}
