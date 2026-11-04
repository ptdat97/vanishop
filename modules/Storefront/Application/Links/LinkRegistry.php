<?php

declare(strict_types=1);

namespace Modules\Storefront\Application\Links;

use Modules\Extension\Contracts\Extensions;
use Modules\Storefront\Contracts\Data\LinkTarget;
use Modules\Storefront\Contracts\Data\ResolvedLink;
use Modules\Storefront\Contracts\LinkResolver;
use Modules\Storefront\Contracts\LinkType;

/**
 * `LinkResolver` qua các `LinkType` đang bật. Ghi nhớ kết quả trong request (binding `scoped`), không cache qua request.
 */
final class LinkRegistry implements LinkResolver
{
    /** @var array<string, array{url: string, label: string}|null> "locale|type|value" => kết quả */
    private array $memo = [];

    /** @var array<string, LinkType>|null */
    private ?array $types = null;

    public function __construct(private readonly Extensions $extensions) {}

    public function resolve(LinkTarget $target, string $locale): ResolvedLink
    {
        return $this->resolveMany([$target], $locale)[0];
    }

    public function resolveMany(array $targets, string $locale): array
    {
        $pending = [];
        foreach ($targets as $target) {
            $key = "{$locale}|{$target->type}|{$target->value}";
            if (! array_key_exists($key, $this->memo)) {
                $pending[$target->type][$target->value] = $key;
            }
        }

        foreach ($pending as $type => $values) {
            $linkType = $this->all()[$type] ?? null;
            $found = $linkType === null ? [] : $linkType->resolve(array_map('strval', array_keys($values)), $locale);
            foreach ($values as $value => $key) {
                $this->memo[$key] = $found[(string) $value] ?? null;
            }
        }

        $resolved = [];
        foreach ($targets as $index => $target) {
            $hit = $this->memo["{$locale}|{$target->type}|{$target->value}"];
            $resolved[$index] = $hit === null ? ResolvedLink::missing() : new ResolvedLink($hit['url'], $target->labelFor($locale) ?? $hit['label'], true);
        }

        return $resolved;
    }

    public function accepts(LinkTarget $target): bool
    {
        $type = $this->all()[$target->type] ?? null;

        return $type !== null && $type->accepts($target->value);
    }

    public function types(): array
    {
        return array_values(array_map(fn (LinkType $type): array => [
            'type' => $type->type(),
            'label' => $type->label(),
            'searchable' => $type->searchable(),
            'options' => $type->options(),
        ], $this->all()));
    }

    public function search(string $type, string $query, string $locale, int $limit = 20): array
    {
        $linkType = $this->all()[$type] ?? null;

        return $linkType !== null && $linkType->searchable() ? $linkType->search($query, $locale, $limit) : [];
    }

    /**
     * @return array<string, LinkType>
     */
    private function all(): array
    {
        return $this->types ??= $this->extensions->implementations(LinkType::TAG, LinkType::class, fn (LinkType $type): string => $type->type());
    }
}
