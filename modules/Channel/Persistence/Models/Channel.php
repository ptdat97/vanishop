<?php

declare(strict_types=1);

namespace Modules\Channel\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Brand\Persistence\Models\Brand;
use Modules\Channel\Persistence\Database\Factories\ChannelFactory;

/**
 * Kênh bán: một điểm bán (web, sàn, POS, app…) chứa 1..N brand.
 *
 * @property int $id
 * @property string $code
 * @property string $type
 * @property string $locale
 * @property string $currency_code
 * @property string $status
 */
final class Channel extends Model
{
    /** @use HasFactory<ChannelFactory> */
    use HasFactory;

    protected $fillable = ['code', 'name', 'type', 'locale', 'currency_code', 'theme', 'status'];

    /**
     * @return BelongsToMany<Brand, $this>
     */
    public function brands(): BelongsToMany
    {
        return $this->belongsToMany(Brand::class, 'channel_brands');
    }

    /**
     * @return HasMany<ChannelDomain, $this>
     */
    public function domains(): HasMany
    {
        return $this->hasMany(ChannelDomain::class);
    }

    protected static function newFactory(): ChannelFactory
    {
        return ChannelFactory::new();
    }
}
