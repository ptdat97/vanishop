<?php

declare(strict_types=1);

namespace Modules\Customer\Contracts\Data;

use Modules\Shared\Contracts\PluginApi;

#[PluginApi]
final readonly class CustomerData
{
    public function __construct(
        public int $id,
        public string $publicId,
        public ?string $phone,
        public ?string $email,
        public ?string $fullName,
        public string $status,
        public bool $registered,
        public ?string $birthDate = null,
        public ?string $gender = null,
        public bool $hasPassword = false,
        /** Ngôn ngữ khách hay dùng (0.3.62, ADR-038 §8), null = chưa biết. */
        public ?string $preferredLocale = null,
        /** Email đã xác minh qua link (0.5.32); false khi chưa có email hoặc vừa đổi email. */
        public bool $emailVerified = false,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->publicId, 'phone' => $this->phone, 'email' => $this->email, 'full_name' => $this->fullName,
            'birth_date' => $this->birthDate, 'gender' => $this->gender, 'registered' => $this->registered, 'has_password' => $this->hasPassword,
            'preferred_locale' => $this->preferredLocale, 'email_verified' => $this->emailVerified,
        ];
    }
}
