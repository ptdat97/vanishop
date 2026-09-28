<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Requests\Admin;

use Illuminate\Support\Facades\Gate;
use Modules\Brand\Persistence\Models\Brand;
use Modules\Identity\Contracts\Data\ScopeRef;

/**
 * Form Request trong brand workspace: kiểm tra quyền catalog.manage trước khi validate.
 */
trait AuthorizesCatalogManage
{
    public function authorize(): bool
    {
        return Gate::allows('catalog.manage', [ScopeRef::brand($this->workspaceBrand()->id)]);
    }

    protected function workspaceBrand(): Brand
    {
        /** @var Brand $brand */
        $brand = $this->attributes->get('workspace_brand');

        return $brand;
    }
}
