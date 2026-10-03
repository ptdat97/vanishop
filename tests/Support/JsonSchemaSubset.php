<?php

declare(strict_types=1);

namespace Tests\Support;

use InvalidArgumentException;

/**
 * Kiểm tra dữ liệu theo tập con JSON Schema dùng trong docs/api/schemas (không thêm dependency — R25):
 * type (kể cả mảng kiểu), required, properties, additionalProperties (bool), items, enum, const, pattern, minimum,
 * format date-time, $ref tới file khác (đường dẫn tương đối). Từ khoá ngoài tập này → lỗi, để schema không âm thầm
 * dùng tính năng mà test không kiểm.
 */
final class JsonSchemaSubset
{
    private const KEYWORDS = ['$schema', '$id', 'title', 'description', 'type', 'required', 'properties', 'additionalProperties', 'items', 'enum', 'const', 'pattern', 'minimum', 'format', '$ref'];

    /**
     * @return list<string> lỗi; rỗng = hợp lệ
     */
    public static function validate(mixed $data, string $schemaFile): array
    {
        $errors = [];
        self::check($data, self::load($schemaFile), dirname($schemaFile), '$', $errors);

        return $errors;
    }

    /**
     * @return array<string, mixed>
     */
    private static function load(string $file): array
    {
        $schema = json_decode((string) file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
        if (! is_array($schema)) {
            throw new InvalidArgumentException("Schema [{$file}] không phải object.");
        }

        return $schema;
    }

    /**
     * @param  array<string, mixed>  $schema
     * @param  list<string>  $errors
     */
    private static function check(mixed $data, array $schema, string $dir, string $path, array &$errors): void
    {
        if ($unknown = array_diff(array_keys($schema), self::KEYWORDS)) {
            throw new InvalidArgumentException("Từ khoá schema chưa hỗ trợ tại {$path}: ".implode(', ', $unknown));
        }
        if (isset($schema['$ref'])) {
            $file = $dir.'/'.$schema['$ref'];
            self::check($data, self::load($file), dirname($file), $path, $errors);

            return;
        }

        if (isset($schema['type'])) {
            $types = (array) $schema['type'];
            if (! array_filter($types, fn (string $type): bool => self::isType($data, $type))) {
                $errors[] = "{$path}: cần kiểu ".implode('|', $types).', nhận '.get_debug_type($data);

                return;
            }
        }
        if (array_key_exists('const', $schema) && $data !== $schema['const']) {
            $errors[] = "{$path}: phải bằng ".json_encode($schema['const']);
        }
        if (isset($schema['enum']) && ! in_array($data, $schema['enum'], true)) {
            $errors[] = "{$path}: ".json_encode($data).' không thuộc '.json_encode($schema['enum']);
        }
        if (is_string($data) && isset($schema['pattern']) && preg_match('/'.str_replace('/', '\/', $schema['pattern']).'/u', $data) !== 1) {
            $errors[] = "{$path}: '{$data}' không khớp {$schema['pattern']}";
        }
        if (is_string($data) && ($schema['format'] ?? null) === 'date-time' && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d+)?(Z|[+-]\d{2}:\d{2})$/', $data) !== 1) {
            $errors[] = "{$path}: '{$data}' không phải date-time RFC 3339";
        }
        if (is_int($data) && isset($schema['minimum']) && $data < $schema['minimum']) {
            $errors[] = "{$path}: {$data} < {$schema['minimum']}";
        }

        if (is_array($data) && ! array_is_list($data)) {
            foreach ($schema['required'] ?? [] as $key) {
                if (! array_key_exists($key, $data)) {
                    $errors[] = "{$path}: thiếu '{$key}'";
                }
            }
            foreach ($data as $key => $value) {
                if (isset($schema['properties'][$key])) {
                    self::check($value, $schema['properties'][$key], $dir, "{$path}.{$key}", $errors);
                } elseif (($schema['additionalProperties'] ?? true) === false) {
                    $errors[] = "{$path}: trường thừa '{$key}'";
                }
            }
        }
        if (is_array($data) && array_is_list($data) && isset($schema['items'])) {
            foreach ($data as $index => $item) {
                self::check($item, $schema['items'], $dir, "{$path}[{$index}]", $errors);
            }
        }
    }

    private static function isType(mixed $data, string $type): bool
    {
        return match ($type) {
            'object' => is_array($data) && ($data === [] || ! array_is_list($data)),
            'array' => is_array($data) && array_is_list($data),
            'string' => is_string($data),
            'integer' => is_int($data),
            'number' => is_int($data) || is_float($data),
            'boolean' => is_bool($data),
            'null' => $data === null,
            default => throw new InvalidArgumentException("Kiểu [{$type}] chưa hỗ trợ."),
        };
    }
}
