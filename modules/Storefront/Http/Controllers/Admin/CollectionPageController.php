<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Contracts\CollectionPages;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Shared\Support\StoreLocale;
use Modules\Storefront\Application\Blocks\BlockLayout;
use Modules\Storefront\Application\PageBlocks;

final class CollectionPageController
{
    public function edit(int $collection, CollectionPages $pages, PageBlocks $blocks): Response
    {
        Gate::authorize('storefront.manage');
        $page = $pages->editable($collection, App::getLocale());
        abort_if($page === null, 404);
        $layout = BlockLayout::collection();

        return Inertia::render('Storefront::Design/Builder', [
            'types' => $blocks->editorTypes(),
            'allowedZones' => $blocks->editorZones($layout),
            'zones' => $layout->toArray(),
            'blocks' => $blocks->editorBlocks($page['blocks']),
            'draftUrls' => [
                'preview' => route('admin.storefront.collection-pages.draft', [$collection, 'preview']),
                'import' => route('admin.storefront.collection-pages.draft', [$collection, 'import']),
            ],
            'configured' => $page['blocks'] !== [],
            'previewUrl' => $page['status'] === 'active' ? route('storefront.collection', $page['slugs'][StoreLocale::default()]) : null,
            'collectionName' => $page['name'],
            'collectionUrl' => Gate::allows('catalog.manage') ? route('admin.catalog.collections.edit', $collection) : null,
        ]);
    }

    public function update(Request $request, int $collection, CollectionPages $pages, PageBlocks $blocks, AuditLogger $audit): RedirectResponse
    {
        Gate::authorize('storefront.manage');
        $layout = BlockLayout::collection();
        $input = $request->validate($blocks->inputRules($layout));
        ['blocks' => $clean, 'media_ids' => $mediaIds] = $blocks->validate($input['blocks'], $layout);

        $pages->save($collection, $clean, $mediaIds);
        $audit->record('storefront.collection_blocks_updated', 'collection', $collection, ['count' => count($clean)]);

        return back()->with('success', __('storefront::messages.collection_page_saved'));
    }
}
