<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\Demo\CatalogSeeder;
use Database\Seeders\Demo\ContentSeeder;
use Database\Seeders\Demo\CouponCodeSeeder;
use Database\Seeders\Demo\OrderSeeder;
use Database\Seeders\Demo\PricingCampaignSeeder;
use Database\Seeders\Demo\PromotionSeeder;
use Database\Seeders\Demo\StorefrontSeeder;
use Database\Seeders\Demo\StoreSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Modules\Extension\Application\Plugins\ManifestRepository;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Extension\Domain\Plugin\PluginStatus;
use Modules\Extension\Persistence\Models\PluginRecord;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Plugin\CouponCode\CouponCodeServiceProvider;
use Plugin\PricingCampaign\PricingCampaignServiceProvider;
use RuntimeException;

/**
 * Dữ liệu demo cho toàn dự án — nhìn được ngay storefront và Admin:
 * kho + cửa hàng, catalog 25 sản phẩm có ảnh thật (danh mục, giá, sale, bộ sưu tập, tồn có mẫu hết / sắp hết), mã giảm
 * giá, nội dung (trang, tin tức, thanh thông báo, banner danh mục, lookbook, bảng size), trang chủ + menu + chân trang,
 * khách và đơn hàng ở nhiều trạng thái.
 *
 *     php artisan db:seed --class=DemoSeeder        # chạy lại an toàn: chỉ bổ sung phần còn thiếu
 *
 * Bật sẵn các plugin không cần kết nối dịch vụ ngoài. Không chạy ở production.
 */
final class DemoSeeder extends Seeder
{
    /** Plugin dùng cho dữ liệu demo, không gọi dịch vụ ngoài. */
    public const PLUGINS = [
        'vani.cms', 'vani.lookbook', 'vani.size-chart', 'vani.storefront-extras', 'vani.scarcity-signals',
        'vani.wishlist', 'vani.media-webp', 'vani.reports', 'vani.bundles', 'vani.promotion-rules',
        PricingCampaignServiceProvider::ID, CouponCodeServiceProvider::ID,
    ];

    public function run(CurrentContext $context, PluginManager $plugins, ManifestRepository $manifests): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('DemoSeeder không chạy ở production.');
        }

        $context->runAs(ContextScope::system('demo seeder'), function () use ($plugins, $manifests): void {
            $this->enablePlugins($plugins, $manifests);
            $this->call([StoreSeeder::class, CatalogSeeder::class, PromotionSeeder::class, ContentSeeder::class, StorefrontSeeder::class, OrderSeeder::class]);
            if ($manifests->find(PricingCampaignServiceProvider::ID) !== null) {
                $this->call(PricingCampaignSeeder::class);
            }
            if ($manifests->find(CouponCodeServiceProvider::ID) !== null) {
                $this->call(CouponCodeSeeder::class);
            }
        });

        // Tài nguyên tĩnh của plugin (JS thanh thông báo, trình chiếu…) + cache trạng thái plugin.
        Artisan::call('vani:plugin:cache');

        if (! StaffUser::query()->exists()) {
            $this->command?->warn('Chưa có tài khoản Admin: php artisan vani:staff:create-owner <email> <tên>');
        }
    }

    private function enablePlugins(PluginManager $plugins, ManifestRepository $manifests): void
    {
        $bundled = $plugins->installBundled()['installed'];
        foreach ([...$bundled, ...self::PLUGINS] as $id) {
            $manifest = $manifests->find($id);
            if ($manifest === null) {
                continue; // plugin không có trong mã nguồn
            }
            $record = PluginRecord::query()->find($id);
            if ($record === null) {
                $plugins->install($id);
                $record = PluginRecord::query()->find($id);
            }
            if ($record !== null && $record->status !== PluginStatus::Enabled) {
                $plugins->enable($id);
            }
            // Provider của plugin vừa bật chưa nạp trong tiến trình này (cấu hình, slot, dịch vụ).
            if (app()->getProviders($manifest->provider) === []) {
                $this->container->register($manifest->provider);
            }
        }
    }
}
