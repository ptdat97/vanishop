<?php

declare(strict_types=1);

namespace Plugin\StorefrontExtras\Infrastructure\Blocks;

final class RepeaterRows
{
    /**
     * @param  array<string, mixed>  $config
     * @param  list<string>  $fields
     * @return array<string, mixed>
     */
    public static function normalize(array $config, string $key, string $legacyPrefix, int $max, array $fields): array
    {
        if (array_key_exists($key, $config)) {
            return $config;
        }

        $rows = [];
        $last = 0;
        for ($index = 1; $index <= $max; $index++) {
            $row = [];
            foreach ($fields as $field) {
                $value = $config["{$legacyPrefix}_{$index}_{$field}"] ?? null;
                if ($value !== null && $value !== '') {
                    $row[$field] = $value;
                }
            }
            $rows[] = $row;
            if ($row !== []) {
                $last = $index;
            }
        }

        return $config + [$key => array_slice($rows, 0, $last)];
    }
}
