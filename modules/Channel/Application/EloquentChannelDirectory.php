<?php

declare(strict_types=1);

namespace Modules\Channel\Application;

use Modules\Channel\Contracts\ChannelDirectory;
use Modules\Channel\Persistence\Models\Channel;

final class EloquentChannelDirectory implements ChannelDirectory
{
    public function all(): array
    {
        return Channel::query()->orderBy('code')->get(['id', 'code', 'name'])
            ->map(fn (Channel $channel): array => ['id' => $channel->id, 'code' => $channel->code, 'name' => (string) $channel->getAttribute('name')])
            ->all();
    }

    public function forBrand(int $brandId): array
    {
        return Channel::query()
            ->whereHas('brands', fn ($query) => $query->whereKey($brandId))
            ->orderBy('code')
            ->get(['id', 'code', 'name'])
            ->map(fn (Channel $channel): array => ['id' => $channel->id, 'code' => $channel->code, 'name' => (string) $channel->getAttribute('name')])
            ->all();
    }
}
