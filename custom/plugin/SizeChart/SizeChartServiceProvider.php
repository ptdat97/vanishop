<?php

declare(strict_types=1);

namespace Plugin\SizeChart;

use Modules\Extension\Contracts\Data\FieldDefinition;
use Modules\Extension\PluginServiceProvider;
use Modules\Identity\Contracts\PermissionRegistry;
use Modules\Storefront\Contracts\Data\SlotView;
use Modules\Storefront\Contracts\StorefrontEnricher;
use Plugin\SizeChart\Infrastructure\SizeChartEnricher;
use Plugin\SizeChart\Infrastructure\SizeCharts;
use Plugin\SizeChart\Persistence\SizeChart;

/**
 * Bảng size và gợi ý size theo chiều cao/cân nặng. Gán bảng ở form sản phẩm, danh mục, thương hiệu (ưu tiên theo thứ tự
 * đó, rồi bảng mặc định). Đặc tả: docs/05-plugin/specs/size-chart.md.
 */
final class SizeChartServiceProvider extends PluginServiceProvider
{
    public const ID = 'vani.size-chart';

    protected function pluginId(): string
    {
        return self::ID;
    }

    public function boot(): void
    {
        // Dữ liệu hiện trên trang công khai: lưu/xoá → cache trang hết hiệu lực trong lô kế tiếp (ADR-051).
        $this->pageCacheModels([SizeChart::class], fn (): array => [self::ID.':charts']);
        // Chuỗi giao diện storefront của plugin theo ngôn ngữ (khoá = câu tiếng Việt, ADR-038); theme ghi đè được.
        $this->translations($this->pluginPath('Resources/lang'), 'vani-size-chart');
        $this->permissions(['size_chart.view' => 'vani-size-chart::texts.size_chart_service_provider.view_size_charts', 'size_chart.manage' => 'vani-size-chart::texts.size_chart_service_provider.manage_size_charts'], [PermissionRegistry::PRESET_CONTENT]);
        $this->adminMenu('size-charts', 'vani-size-chart::texts.size_chart_service_provider.size_charts', 'admin.plugins.vani-size-chart.index', 'size_chart.view', 130, group: 'catalog');
        $this->adminRoutes($this->pluginPath('Http/routes/admin.php'));
        $this->adminPages('SizeChart', $this->pluginPath('Resources/js/Pages'));

        $this->contribute(StorefrontEnricher::TAG, SizeChartEnricher::class);
        $this->storefrontViews($this->pluginPath('Resources/views'), 'vani-size-chart');
        $this->onSlot('vani.storefront.pdp.add_to_cart_fields', fn (array $product): ?SlotView => isset($product['extensions'][self::ID])
            ? new SlotView('vani-size-chart::dialog', ['chart' => $product['extensions'][self::ID]])
            : null);

        $this->assignmentSection('product', 'vani-size-chart::texts.size_chart_service_provider.leave_empty_use_category_brand');
        $this->assignmentSection('category', 'vani-size-chart::texts.size_chart_service_provider.applies_products_whose_primary_category');
        $this->assignmentSection('brand', 'vani-size-chart::texts.size_chart_service_provider.applies_brand_s_products_when');
    }

    private function assignmentSection(string $resource, string $help): void
    {
        $charts = fn (): SizeCharts => $this->app->make(SizeCharts::class);
        // Lựa chọn đọc từ DB khi mở/lưu form (closure — Core 0.3.44), không truy vấn lúc boot.
        $fields = fn (): array => [FieldDefinition::select('chart', __('vani-size-chart::texts.size_chart_service_provider.size_charts'), $charts()->options(), help: $help)];

        $this->adminFormSection($resource, 'chart', 'vani-size-chart::texts.size_chart_service_provider.size_charts', $fields,
            load: fn (int $id): array => ['chart' => ($chartId = $charts()->assignedChart($resource, $id)) === null ? null : (string) $chartId],
            save: fn (int $id, array $values) => $charts()->assign($resource, $id, ($values['chart'] ?? null) === null || $values['chart'] === '' ? null : (int) $values['chart']),
            order: 300,
        );
    }
}
