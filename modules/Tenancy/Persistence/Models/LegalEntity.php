<?php

declare(strict_types=1);

namespace Modules\Tenancy\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Tenancy\Persistence\Database\Factories\LegalEntityFactory;

/**
 * Pháp nhân (công ty có MST) — xuất hoá đơn, nhận tiền.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $tax_code
 */
final class LegalEntity extends Model
{
    /** @use HasFactory<LegalEntityFactory> */
    use HasFactory;

    protected $fillable = ['code', 'name', 'tax_code', 'registered_address', 'status'];

    protected static function newFactory(): LegalEntityFactory
    {
        return LegalEntityFactory::new();
    }
}
