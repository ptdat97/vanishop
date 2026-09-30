<?php

declare(strict_types=1);

namespace Modules\Tenancy\Contracts\Data;

/**
 * Khai báo một cấu hình để Admin sinh form. `secret` được mã hoá khi lưu và không trả về giao diện.
 */
final readonly class SettingDefinition
{
    public const TYPES = ['string', 'secret', 'text', 'int', 'bool', 'select'];

    /**
     * @param  list<string>  $scopes  phạm vi được phép đặt: owner | legal_entity | brand | channel
     * @param  array<string, string>  $options  (select) giá trị => nhãn
     */
    public function __construct(
        public string $namespace,
        public string $key,
        public string $label,
        public string $type = 'string',
        public mixed $default = null,
        public array $scopes = [SettingsScope::OWNER, SettingsScope::BRAND],
        public array $options = [],
        public ?string $help = null,
        /** (select) lấy lựa chọn từ mã (`code()`) các implementation của extension point này, vd. `ReturnPolicy::TAG`. */
        public ?string $optionsFromTag = null,
    ) {}
}
