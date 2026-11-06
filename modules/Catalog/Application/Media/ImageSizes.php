<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Media;

use Illuminate\Validation\ValidationException;
use Modules\Catalog\Contracts\Data\ImageSize;
use Modules\Catalog\Contracts\ImageSizeProvider;
use Modules\Extension\Contracts\Extensions;
use Modules\Tenancy\Contracts\Settings;
use Throwable;

/**
 * Các kích thước ảnh thu nhỏ được phép tạo (0.3.87) — cấu hình ở Admin → Thư viện ảnh → Kích thước ảnh:
 *
 * - **Bề rộng chung** (`media.widths`, mặc định theo `vanishop.media.cache.widths`): ảnh bất kỳ (logo, banner, bài viết…),
 *   chỉ thu nhỏ theo bề rộng, giữ tỉ lệ gốc — nguồn của `srcset` thường.
 * - **Ảnh sản phẩm** (`media.product_ratio`, mặc định 2:3): cùng các bề rộng chung nhưng cắt về đúng tỉ lệ để lưới sản phẩm
 *   đều nhau; `original` = giữ tỉ lệ gốc.
 * - **Kích thước có tên** do plugin khai báo (`ImageSizeProvider`), nhân viên ghi đè rộng/cao/cắt khung (`media.size_overrides`).
 *
 * Chỉ các kích thước này được tạo khi có request (chặn URL tuỳ ý làm đầy đĩa/CPU).
 */
final class ImageSizes
{
    public const PRODUCT = 'product';

    public const RATIOS = ['original' => 'Giữ tỉ lệ gốc', '2:3' => '2:3 (dọc, thời trang)', '3:4' => '3:4', '4:5' => '4:5', '1:1' => '1:1 (vuông)'];

    public const DEFAULT_RATIO = '2:3';

    public const MAX_WIDTHS = 8;

    private const NAMESPACE = 'core';

    /** @var array{widths: list<int>, ratio: ?array{int, int}, named: array<string, ImageSize>, variants: array<string, ImageSize>}|null */
    private ?array $memo = null;

    public function __construct(
        private readonly Settings $settings,
        private readonly Extensions $extensions,
    ) {}

    /**
     * @return list<int> tăng dần
     */
    public function widths(): array
    {
        return $this->load()['widths'];
    }

    /**
     * Tỉ lệ ảnh sản phẩm [rộng, cao]; null = giữ tỉ lệ gốc.
     *
     * @return array{int, int}|null
     */
    public function productRatio(): ?array
    {
        return $this->load()['ratio'];
    }

    public function quality(): int
    {
        return max(1, min(100, (int) $this->settings->get(self::NAMESPACE, 'media.quality', config('vanishop.media.cache.quality', 80))));
    }

    /**
     * Kích thước theo bề rộng: `null` nhóm = thu nhỏ giữ tỉ lệ, `product` = cắt theo tỉ lệ ảnh sản phẩm.
     *
     * @return list<ImageSize>
     */
    public function responsive(?string $group = null): array
    {
        $ratio = $group === self::PRODUCT ? $this->productRatio() : null;

        return array_map(fn (int $width): ImageSize => $ratio === null
            ? new ImageSize("w{$width}", "{$width}px", $width)
            : new ImageSize(self::PRODUCT.".w{$width}", "Sản phẩm {$width}px", $width, (int) round($width * $ratio[1] / $ratio[0]), true), $this->widths());
    }

    /**
     * Kích thước có tên (plugin), đã áp ghi đè của nhân viên.
     */
    public function named(string $name): ?ImageSize
    {
        return $this->load()['named'][$name] ?? null;
    }

    /**
     * @return list<ImageSize>
     */
    public function namedSizes(): array
    {
        return array_values($this->load()['named']);
    }

    /**
     * Mọi kích thước được phép tạo, theo khoá tên file.
     *
     * @return array<string, ImageSize>
     */
    public function variants(): array
    {
        return $this->load()['variants'];
    }

    public function find(string $key): ?ImageSize
    {
        return $this->load()['variants'][$key] ?? null;
    }

    /**
     * Cho màn hình Admin: kích thước có tên kèm plugin khai báo và giá trị mặc định.
     *
     * @return list<array{name: string, label: string, plugin: ?string, default: array<string, mixed>, current: array<string, mixed>, overridden: bool}>
     */
    public function catalog(): array
    {
        $overrides = $this->overrides();
        $rows = [];
        foreach ($this->declared() as $name => [$size, $plugin]) {
            $current = $this->load()['named'][$name] ?? $size;
            $rows[] = ['name' => $name, 'label' => $size->label, 'plugin' => $plugin, 'default' => $size->toArray(), 'current' => $current->toArray(), 'overridden' => isset($overrides[$name])];
        }

        return $rows;
    }

    /**
     * Lưu cấu hình từ Admin (đã validate dạng ở controller).
     *
     * @param  array{widths: list<int>, product_ratio: string, quality: int, overrides?: array<string, array{width: int, height: ?int, crop: bool}|null>}  $input
     */
    public function save(array $input): void
    {
        $widths = array_values(array_unique(array_map('intval', $input['widths'])));
        sort($widths);
        if ($widths === [] || count($widths) > self::MAX_WIDTHS) {
            throw ValidationException::withMessages(['widths' => 'Cần 1–'.self::MAX_WIDTHS.' bề rộng.']);
        }

        $declared = $this->declared();
        $overrides = [];
        foreach ((array) ($input['overrides'] ?? []) as $name => $override) {
            if ($override === null || ! isset($declared[$name])) {
                continue;
            }
            try {
                $size = new ImageSize($name, $declared[$name][0]->label, (int) $override['width'], $override['height'] === null ? null : (int) $override['height'], (bool) $override['crop']);
            } catch (\InvalidArgumentException $exception) {
                throw ValidationException::withMessages(["overrides.{$name}" => $exception->getMessage()]);
            }
            if ($size->toArray() !== $declared[$name][0]->toArray()) {
                $overrides[$name] = ['width' => $size->width, 'height' => $size->height, 'crop' => $size->crop];
            }
        }

        $this->settings->set(self::NAMESPACE, 'media.widths', $widths);
        $this->settings->set(self::NAMESPACE, 'media.product_ratio', array_key_exists($input['product_ratio'], self::RATIOS) ? $input['product_ratio'] : self::DEFAULT_RATIO);
        $this->settings->set(self::NAMESPACE, 'media.quality', max(1, min(100, (int) $input['quality'])));
        $this->settings->set(self::NAMESPACE, 'media.size_overrides', $overrides);
        $this->memo = null;
    }

    public function ratioCode(): string
    {
        $code = (string) $this->settings->get(self::NAMESPACE, 'media.product_ratio', config('vanishop.media.cache.product_ratio', self::DEFAULT_RATIO));

        return array_key_exists($code, self::RATIOS) ? $code : self::DEFAULT_RATIO;
    }

    /**
     * @return array{widths: list<int>, ratio: ?array{int, int}, named: array<string, ImageSize>, variants: array<string, ImageSize>}
     */
    private function load(): array
    {
        if ($this->memo !== null) {
            return $this->memo;
        }

        $widths = array_values(array_unique(array_filter(
            array_map('intval', (array) $this->settings->get(self::NAMESPACE, 'media.widths', config('vanishop.media.cache.widths', [160, 400, 800, 1600]))),
            fn (int $width): bool => $width >= ImageSize::MIN && $width <= ImageSize::MAX,
        )));
        sort($widths);
        $code = $this->ratioCode();
        $ratio = $code === 'original' ? null : array_map('intval', explode(':', $code));

        $overrides = $this->overrides();
        $named = [];
        foreach ($this->declared() as $name => [$size]) {
            $override = $overrides[$name] ?? null;
            $named[$name] = $override === null ? $size : new ImageSize($name, $size->label, (int) $override['width'], $override['height'] === null ? null : (int) $override['height'], (bool) $override['crop']);
        }

        $this->memo = ['widths' => $widths, 'ratio' => $ratio === null ? null : [$ratio[0], $ratio[1]], 'named' => $named, 'variants' => []];
        foreach ([...$this->responsive(), ...$this->responsive(self::PRODUCT), ...array_values($named)] as $size) {
            $this->memo['variants'][$size->key()] ??= $size;
        }

        return $this->memo;
    }

    /**
     * Kích thước plugin khai báo (tên → [kích thước, plugin]); plugin lỗi bị bỏ qua.
     *
     * @return array<string, array{ImageSize, ?string}>
     */
    private function declared(): array
    {
        $result = [];
        foreach ($this->extensions->implementations(ImageSizeProvider::TAG, ImageSizeProvider::class, fn (object $provider): string => $provider::class) as $provider) {
            $sizes = $this->extensions->call($provider, fn (): array => $provider->sizes(), [], 'catalog.image_sizes');
            foreach ($sizes as $size) {
                if ($size instanceof ImageSize && ! isset($result[$size->name])) {
                    $result[$size->name] = [$size, $this->extensions->ownerOf($provider)];
                }
            }
        }

        return $result;
    }

    /**
     * @return array<string, array{width: int, height: ?int, crop: bool}>
     */
    private function overrides(): array
    {
        try {
            $value = $this->settings->get(self::NAMESPACE, 'media.size_overrides', []);
        } catch (Throwable) {
            return [];
        }

        return is_array($value) ? $value : [];
    }
}
