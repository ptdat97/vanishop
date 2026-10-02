<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final class CatalogHomeController
{
    public function __invoke(): RedirectResponse
    {
        Gate::authorize('catalog.view');

        return redirect()->route('admin.catalog.products.index');
    }
}
