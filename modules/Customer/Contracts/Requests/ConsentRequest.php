<?php

declare(strict_types=1);

namespace Modules\Customer\Contracts\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Bật/tắt đồng ý nhận tin theo kênh — một hợp đồng đầu vào cho Storefront API (`PUT /me/consents`) và form hồ sơ của Blade
 * (ADR-044 §2). Kênh = mã kênh liên lạc (`email`, `sms`, `zns`, kênh plugin), không giữ danh sách cố định.
 */
final class ConsentRequest extends FormRequest
{
    public const CHANNEL_PATTERN = '/^[a-z][a-z0-9_]{1,31}$/';

    public const PURPOSES = ['marketing'];

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'channel' => ['required', 'string', 'regex:'.self::CHANNEL_PATTERN],
            'purpose' => ['required', Rule::in(self::PURPOSES)],
            'granted' => ['required', 'boolean'],
        ];
    }
}
