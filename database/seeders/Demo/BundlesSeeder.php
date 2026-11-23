<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Illuminate\Database\Seeder;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Plugin\Bundles\BundlesServiceProvider;
use Plugin\Bundles\Database\BundlesDemoContent;
use RuntimeException;

final class BundlesSeeder extends Seeder
{
    public function run(CurrentContext $context, BundlesDemoContent $content): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('BundlesSeeder không chạy ở production.');
        }
        if (vani_plugin(BundlesServiceProvider::ID)?->enabled !== true) {
            throw new RuntimeException('Cần bật vani.bundles để tạo combo mẫu.');
        }

        $context->runAs(ContextScope::system('bundles demo seeder'), fn () => $content->seed());
    }
}
