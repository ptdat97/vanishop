<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * Từ điển dữ liệu sinh từ DB thật (reference-parity §3.20): bảng, cột, index, khoá ngoại của Core →
 * docs/07-database/schema.generated.md. Bảng plugin (`plg_*`) do tài liệu của plugin mô tả nên bỏ qua.
 * `--check` (CI, sau `migrate:fresh` trên MySQL): mã thoát 1 khi tài liệu không khớp schema.
 */
final class SchemaDocsCommand extends Command
{
    protected $signature = 'vani:docs:schema {--check : Chỉ so với file hiện có, không ghi} {--path=docs/07-database/schema.generated.md}';

    protected $description = 'Sinh từ điển dữ liệu Markdown từ schema DB (bảng Core)';

    public function handle(): int
    {
        $path = base_path((string) $this->option('path'));
        $markdown = $this->render();

        if ($this->option('check')) {
            if (! is_file($path) || file_get_contents($path) !== $markdown) {
                $this->error('Từ điển dữ liệu không khớp schema. Chạy `php artisan vani:docs:schema` trên DB MySQL đã migrate:fresh rồi commit.');

                return self::FAILURE;
            }
            $this->info('Từ điển dữ liệu khớp schema.');

            return self::SUCCESS;
        }

        file_put_contents($path, $markdown);
        $this->info("Đã ghi {$path}.");

        return self::SUCCESS;
    }

    private function render(): string
    {
        $tables = array_values(array_filter(
            array_map(fn (array $table): string => (string) $table['name'], Schema::getTables(Schema::getConnection()->getDriverName() === 'sqlite' ? null : Schema::getConnection()->getDatabaseName())),
            fn (string $name): bool => ! str_starts_with($name, 'plg_'),
        ));
        sort($tables);

        $lines = [
            '# Từ điển dữ liệu (sinh tự động)',
            '',
            '> Sinh bởi `php artisan vani:docs:schema` từ schema '.Schema::getConnection()->getDriverName().' sau `migrate:fresh` — **không sửa tay**. CI chạy `--check`. Ý nghĩa nghiệp vụ của bảng: [database](database.md). Bảng plugin (`plg_*`) mô tả trong tài liệu của plugin.',
            '',
            '| Bảng | Số cột |',
            '|---|---|',
        ];
        foreach ($tables as $table) {
            $lines[] = "| [`{$table}`](#{$table}) | ".count(Schema::getColumns($table)).' |';
        }

        foreach ($tables as $table) {
            $lines[] = '';
            $lines[] = "## {$table}";
            $lines[] = '';
            $lines[] = '| Cột | Kiểu | Null | Mặc định |';
            $lines[] = '|---|---|---|---|';
            foreach (Schema::getColumns($table) as $column) {
                $default = $column['default'] === null ? '' : '`'.str_replace('|', '\|', (string) $column['default']).'`';
                $lines[] = "| `{$column['name']}` | {$column['type']} | ".($column['nullable'] ? 'có' : '').' | '.$default.' |';
            }

            $indexes = Schema::getIndexes($table);
            usort($indexes, fn (array $a, array $b): int => strcmp((string) $a['name'], (string) $b['name']));
            if ($indexes !== []) {
                $lines[] = '';
                $lines[] = 'Index: '.implode('; ', array_map(
                    fn (array $index): string => ($index['primary'] ? 'PK' : ($index['unique'] ? 'unique' : 'index')).' ('.implode(', ', $index['columns']).')',
                    $indexes,
                ));
            }

            $foreign = Schema::getForeignKeys($table);
            usort($foreign, fn (array $a, array $b): int => strcmp(implode(',', $a['columns']), implode(',', $b['columns'])));
            if ($foreign !== []) {
                $lines[] = '';
                $lines[] = 'Khoá ngoại: '.implode('; ', array_map(
                    fn (array $key): string => implode(', ', $key['columns'])." → {$key['foreign_table']}(".implode(', ', $key['foreign_columns']).')'.($key['on_delete'] !== null && strtolower((string) $key['on_delete']) !== 'no action' && strtolower((string) $key['on_delete']) !== 'restrict' ? ' on delete '.strtolower((string) $key['on_delete']) : ''),
                    $foreign,
                ));
            }
        }

        return implode("\n", $lines)."\n";
    }
}
