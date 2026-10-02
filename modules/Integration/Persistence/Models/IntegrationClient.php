<?php

declare(strict_types=1);

namespace Modules\Integration\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Danh tính một hệ thống ngoài gọi Integration API / nhận webhook (ERP, ODO, POS).
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $status
 * @property list<string> $scopes
 * @property list<string>|null $ip_allowlist
 * @property int $rate_limit
 */
final class IntegrationClient extends Model
{
    protected $fillable = ['code', 'name', 'status', 'scopes', 'ip_allowlist', 'rate_limit'];

    protected function casts(): array
    {
        return ['scopes' => 'array', 'ip_allowlist' => 'array', 'rate_limit' => 'integer'];
    }

    /**
     * @return HasMany<ClientKey, $this>
     */
    public function keys(): HasMany
    {
        return $this->hasMany(ClientKey::class, 'client_id');
    }

    /**
     * @return HasMany<WebhookSubscription, $this>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(WebhookSubscription::class, 'client_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopes, true);
    }
}
