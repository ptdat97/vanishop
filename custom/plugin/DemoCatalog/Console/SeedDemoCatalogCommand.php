<?php

declare(strict_types=1);

namespace Plugin\DemoCatalog\Console;

use Illuminate\Console\Command;
use InvalidArgumentException;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Plugin\DemoCatalog\Infrastructure\DemoCatalogBuilder;
use Plugin\DemoCatalog\Infrastructure\ImageResizer;

/**
 * Nhập catalog demo có ảnh. Chạy lại an toàn (idempotent theo mã sản phẩm, ảnh khử trùng theo checksum).
 */
final class SeedDemoCatalogCommand extends Command
{
    protected $signature = 'vani:demo:catalog
        {--source= : Thư mục ảnh (mặc định VANI_DEMO_IMAGES / VaniCommerce/public/image/catalog/products)}
        {--limit= : Chỉ nhập N sản phẩm đầu}
        {--dry-run : Chỉ in kế hoạch, không ghi gì}';

    protected $description = 'Nhập sản phẩm demo có ảnh thật (plugin vani.demo-catalog)';

    public function handle(DemoCatalogBuilder $builder, CurrentContext $context): int
    {
        $source = (string) ($this->option('source') ?: config('vani.demo-catalog.source'));
        $limit = $this->option('limit') === null ? null : max(1, (int) $this->option('limit'));

        try {
            if ($this->option('dry-run')) {
                $plan = $builder->plan($source, $limit);
                $this->table(['Nhóm ảnh', 'Mã', 'Tên', 'Số ảnh', 'Giá'], array_map(fn (array $row): array => [$row['key'], $row['style_code'], $row['name'], $row['images'], number_format($row['price'], 0, ',', '.')], $plan));
                $this->info(count($plan).' sản phẩm sẽ được nhập.');

                return self::SUCCESS;
            }

            $resizer = new ImageResizer((int) config('vani.demo-catalog.max_dimension', 1600));
            $summary = $context->runAs(ContextScope::system('vani:demo:catalog'), fn (): array => $builder->build($source, $resizer, $limit,
                fn (string $key, string $name, bool $created, int $images) => $this->line(sprintf('  %s %-10s %s (%d ảnh)', $created ? '+' : '=', $key, $name, $images))));
        } catch (InvalidArgumentException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Xong: {$summary['products']} sản phẩm ({$summary['created']} mới), {$summary['images']} ảnh mới, {$summary['skus']} SKU.");
        $summary['stock_location'] === null
            ? $this->warn('Chưa có kho giao online do VaniShop quản lý — chưa đặt tồn. Tạo kho trong Admin → Tồn kho rồi chạy lại.')
            : $this->info("Tồn đầu kỳ đặt tại kho {$summary['stock_location']}.");

        return self::SUCCESS;
    }
}
