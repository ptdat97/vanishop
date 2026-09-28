<?php

declare(strict_types=1);

namespace Modules\Promotion\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $promotion_id
 * @property string $rule_type
 * @property array<string, mixed> $config
 */
final class PromotionRuleRecord extends Model
{
    protected $table = 'promotion_rules';

    protected $fillable = ['promotion_id', 'rule_type', 'config'];

    protected function casts(): array
    {
        return ['config' => 'array'];
    }
}
