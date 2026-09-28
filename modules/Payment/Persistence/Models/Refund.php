<?php

declare(strict_types=1);

namespace Modules\Payment\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

final class Refund extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['payment_id' => 'integer', 'amount' => 'integer'];
    }
}
