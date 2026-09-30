<?php

declare(strict_types=1);

namespace Modules\Customer\Contracts\Data;

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
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->publicId, 'phone' => $this->phone, 'email' => $this->email, 'full_name' => $this->fullName,
            'birth_date' => $this->birthDate, 'gender' => $this->gender, 'registered' => $this->registered, 'has_password' => $this->hasPassword,
        ];
    }
}
