<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Response;
use Modules\Brand\Http\Controllers\BrandWorkspaceHome;

final class CatalogHomeController
{
    public function __invoke(BrandWorkspaceHome $home): Response|RedirectResponse
    {
        Gate::authorize('catalog.view');

        return $home->respond('admin.catalog.products.index', 'Catalog', 'Chọn brand để quản lý sản phẩm, danh mục, thuộc tính, màu và size.');
    }
}
