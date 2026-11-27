<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Shared\Contracts\PageCacheInvalidator;

/**
 * Admin → Giao diện → "Xoá cache trang" (ADR-051 §4): xoá ngay toàn bộ cache trang công khai (và CDN qua event).
 */
final class PageCacheController
{
    public function __invoke(PageCacheInvalidator $invalidator, AuditLogger $audit): RedirectResponse
    {
        Gate::authorize('storefront.manage');
        $invalidator->flush();
        $audit->record('storefront.page_cache_cleared', 'storefront', 'page_cache');

        return back()->with('success', __('storefront::messages.page_cache_cleared'));
    }
}
