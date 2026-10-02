<?php

declare(strict_types=1);

namespace Modules\Storefront\Application\Theme;

use Illuminate\Support\Facades\Log;
use JsonException;
use Modules\Tenancy\Contracts\Settings;

/**
 * Theme của native storefront (storefront §2, ADR-025): một theme đang hoạt động cho cả cửa hàng (cấu hình
 * `core.theme`), view tìm theo chuỗi theme đang hoạt động → theme cha → … → `vani-base`.
 */
final class Themes
{
    public const BASE = 'vani-base';

    /** Giới hạn độ sâu chuỗi theme cha (chống vòng lặp do khai báo sai). */
    private const MAX_DEPTH = 5;

    /** @var array<string, Theme>|null */
    private ?array $themes = null;

    public function __construct(
        private readonly string $path,
        private readonly Settings $settings,
        private readonly string $default,
    ) {}

    /**
     * @return array<string, Theme>
     */
    public function all(): array
    {
        if ($this->themes !== null) {
            return $this->themes;
        }

        $this->themes = [];
        foreach (glob($this->path.'/*/theme.json') ?: [] as $file) {
            try {
                $data = json_decode((string) file_get_contents($file), true, 16, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                Log::warning('Bỏ qua theme có theme.json lỗi.', ['file' => $file, 'error' => $exception->getMessage()]);

                continue;
            }

            $name = basename(dirname($file));
            $this->themes[$name] = new Theme(
                name: $name,
                label: (string) ($data['label'] ?? $name),
                parent: isset($data['parent']) && $data['parent'] !== '' ? (string) $data['parent'] : null,
                path: dirname($file),
                tokens: array_filter(array_map('strval', (array) ($data['tokens'] ?? [])), self::safeValue(...)),
            );
        }
        ksort($this->themes);

        return $this->themes;
    }

    /**
     * Theme đang hoạt động; cấu hình trỏ tới theme không có → mặc định → `vani-base`.
     */
    public function active(): Theme
    {
        $themes = $this->all();
        $name = (string) $this->settings->get('core', 'theme', $this->default);

        return $themes[$name] ?? $themes[$this->default] ?? $themes[self::BASE]
            ?? throw new \RuntimeException('Không tìm thấy theme '.self::BASE.' trong '.$this->path);
    }

    /**
     * Chuỗi theme để tìm view: theme đang hoạt động → cha → … (luôn kết thúc ở `vani-base`).
     *
     * @return list<Theme>
     */
    public function chain(?Theme $theme = null): array
    {
        $themes = $this->all();
        $chain = [];
        $current = $theme ?? $this->active();

        while ($current !== null && count($chain) < self::MAX_DEPTH && ! isset($chain[$current->name])) {
            $chain[$current->name] = $current;
            $current = $current->parent === null ? null : ($themes[$current->parent] ?? null);
        }

        if (! isset($chain[self::BASE]) && isset($themes[self::BASE])) {
            $chain[self::BASE] = $themes[self::BASE];
        }

        return array_values($chain);
    }

    /**
     * Token giao diện: mặc định của chuỗi theme (cha trước, con ghi đè) rồi cấu hình `theme.tokens` của cửa hàng.
     *
     * @return array<string, string>
     */
    public function tokens(): array
    {
        $tokens = [];
        foreach (array_reverse($this->chain()) as $theme) {
            $tokens = [...$tokens, ...$theme->tokens];
        }

        $configured = $this->settings->get('core', 'theme.tokens');
        if (is_string($configured) && $configured !== '') {
            try {
                $configured = json_decode($configured, true, 4, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                $configured = [];
            }
        }

        foreach ((array) $configured as $name => $value) {
            if (is_string($name) && preg_match('/^[a-z0-9-]{1,40}$/', $name) === 1 && is_scalar($value) && self::safeValue((string) $value)) {
                $tokens[$name] = (string) $value;
            }
        }

        return $tokens;
    }

    /**
     * Giá trị token đi vào `<style>`: chỉ cho ký tự của màu/độ dài/font, chặn `;{}<>` để không thoát khỏi khai báo CSS.
     */
    private static function safeValue(string $value): bool
    {
        return preg_match('/^[#\w\s.,%()\'"\/-]{1,120}$/u', $value) === 1;
    }
}
