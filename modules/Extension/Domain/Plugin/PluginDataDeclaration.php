<?php

declare(strict_types=1);

namespace Modules\Extension\Domain\Plugin;

/**
 * Khai báo dữ liệu của plugin trong manifest (`data`, 0.3.24):
 * - `owned`: bảng `plg_*` do migration của plugin tạo — `uninstall --purge` xoá (rollback migration);
 * - `references`: cột của plugin trỏ sang dữ liệu khác, `"plg_bang.cot" => "bang.cot"` (Core hoặc plugin khác) — plugin
 *   được trỏ tới không purge được khi plugin này còn cài;
 * - `retained`: bảng phải lưu giữ (chứng từ, pháp lý), ⊆ `owned` — purge cần xác nhận riêng (`--drop-retained`).
 */
final readonly class PluginDataDeclaration
{
    private const TABLE = '/^plg_[a-z0-9_]+$/';

    private const COLUMN = '/^[a-z0-9_]+\.[a-z0-9_]+$/';

    /**
     * @param  list<string>  $owned
     * @param  array<string, string>  $references
     * @param  list<string>  $retained
     */
    public function __construct(
        public array $owned = [],
        public array $references = [],
        public array $retained = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data, string $path): self
    {
        $owned = array_values(array_map('strval', (array) ($data['owned'] ?? [])));
        $references = array_map('strval', (array) ($data['references'] ?? []));
        $retained = array_values(array_map('strval', (array) ($data['retained'] ?? [])));

        foreach ($owned as $table) {
            if (preg_match(self::TABLE, $table) !== 1) {
                throw InvalidManifest::because($path, "data.owned [{$table}] phải là bảng plg_* (ADR-027)");
            }
        }
        foreach ($retained as $table) {
            if (! in_array($table, $owned, true)) {
                throw InvalidManifest::because($path, "data.retained [{$table}] phải nằm trong data.owned");
            }
        }
        foreach ($references as $from => $to) {
            if (preg_match(self::COLUMN, (string) $from) !== 1 || preg_match(self::COLUMN, $to) !== 1) {
                throw InvalidManifest::because($path, "data.references [{$from} → {$to}] phải có dạng bang.cot");
            }
            if (! in_array(explode('.', (string) $from)[0], $owned, true)) {
                throw InvalidManifest::because($path, "data.references [{$from}] phải bắt đầu từ bảng trong data.owned");
            }
        }

        return new self($owned, $references, $retained);
    }

    /**
     * Bảng (của người khác) mà plugin này trỏ tới.
     *
     * @return list<string>
     */
    public function referencedTables(): array
    {
        return array_values(array_unique(array_map(fn (string $to): string => explode('.', $to)[0], array_values($this->references))));
    }
}
