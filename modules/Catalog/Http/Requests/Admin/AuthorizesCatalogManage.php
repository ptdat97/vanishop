<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Requests\Admin;

use Illuminate\Support\Facades\Gate;

/**
 * Form Request của Catalog Admin: kiểm tra quyền catalog.manage trước khi validate.
 */
trait AuthorizesCatalogManage
{
    public function authorize(): bool
    {
        return Gate::allows('catalog.manage');
    }
}
