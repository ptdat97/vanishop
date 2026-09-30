<?php

declare(strict_types=1);

namespace Modules\Customer\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $customer_id
 * @property string|null $label
 * @property string $full_name
 * @property string $phone
 * @property string $province_code
 * @property string $province_name
 * @property string $ward_code
 * @property string $ward_name
 * @property string $street_line
 * @property bool $is_default
 */
final class CustomerAddress extends Model
{
    protected $fillable = ['customer_id', 'label', 'full_name', 'phone', 'province_code', 'province_name', 'ward_code', 'ward_name', 'street_line', 'is_default'];

    protected function casts(): array
    {
        return ['customer_id' => 'integer', 'is_default' => 'boolean'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toView(): array
    {
        return [
            'id' => $this->id, 'label' => $this->label, 'full_name' => $this->full_name, 'phone' => $this->phone,
            'province_code' => $this->province_code, 'province_name' => $this->province_name,
            'ward_code' => $this->ward_code, 'ward_name' => $this->ward_name, 'street_line' => $this->street_line,
            'is_default' => $this->is_default,
        ];
    }
}
