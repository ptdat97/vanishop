<?php

declare(strict_types=1);

namespace Modules\Tenancy\Contracts\Data;

/**
 * Khai báo một cấu hình để Admin sinh form. `secret` được mã hoá khi lưu và không trả về giao diện.
 */
final readonly class SettingDefinition
{
    /** `media` (0.3.87): id ảnh của Thư viện ảnh (ô chọn ảnh trong Admin). */
    public const TYPES = ['string', 'secret', 'text', 'int', 'bool', 'select', 'media'];

    /**
     * @param  array<string, string>  $options  (select) giá trị => nhãn
     */
    public function __construct(
        public string $namespace,
        public string $key,
        public string $label,
        public string $type = 'string',
        public mixed $default = null,
        public array $options = [],
        public ?string $help = null,
        /** (select) lấy lựa chọn từ mã (`code()`) các implementation của extension point này, vd. `ReturnPolicy::TAG`. */
        public ?string $optionsFromTag = null,
        /**
         * Rule validate thêm (Laravel) cho giá trị sau khi ép kiểu, vd. `['regex:/^#[0-9a-f]{6}$/i']` (0.3.43). Giá trị
         * rỗng (đặt lại mặc định) không bị kiểm tra.
         *
         * @var list<mixed>
         */
        public array $rules = [],
    ) {}
}
