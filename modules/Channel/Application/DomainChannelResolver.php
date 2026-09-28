<?php

declare(strict_types=1);

namespace Modules\Channel\Application;

use Modules\Channel\Contracts\ChannelResolver;
use Modules\Channel\Contracts\Data\ChannelData;
use Modules\Channel\Persistence\Models\Channel;
use Modules\Channel\Persistence\Models\ChannelDomain;

final class DomainChannelResolver implements ChannelResolver
{
    public function resolve(string $host, string $path): ?ChannelData
    {
        $path = '/'.ltrim($path, '/');

        $domain = ChannelDomain::query()
            ->with(['channel' => fn ($query) => $query->where('status', 'active')->with('brands:id')])
            ->where('host', strtolower($host))
            ->orderByRaw('LENGTH(path_prefix) DESC')
            ->get()
            ->first(fn (ChannelDomain $domain): bool => $domain->channel !== null
                && ($domain->path_prefix === '' || $path === $domain->path_prefix || str_starts_with($path, rtrim($domain->path_prefix, '/').'/')));

        if ($domain === null) {
            return null;
        }

        return $this->toData($domain->channel, $domain->path_prefix);
    }

    public function byCode(string $code): ?ChannelData
    {
        $channel = Channel::query()->with('brands:id')->where('code', $code)->where('status', 'active')->first();

        return $channel === null ? null : $this->toData($channel, (string) $channel->domains()->where('is_primary', true)->value('path_prefix'));
    }

    private function toData(Channel $channel, string $pathPrefix): ChannelData
    {
        return new ChannelData(
            id: $channel->id,
            code: $channel->code,
            type: $channel->type,
            locale: $channel->locale,
            currencyCode: $channel->currency_code,
            brandIds: $channel->brands->pluck('id')->sort()->values()->all(),
            pathPrefix: $pathPrefix,
        );
    }
}
