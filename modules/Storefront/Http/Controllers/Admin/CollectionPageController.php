<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Contracts\CollectionPages;
use Modules\Extension\Contracts\Data\FieldDefinition;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Shared\Support\StoreLocale;
use Modules\Storefront\Application\PageBlocks;
use Modules\Storefront\Contracts\StorefrontBlock;

final class CollectionPageController
{
    public function edit(int $collection, CollectionPages $pages, PageBlocks $blocks): Response
    {
        Gate::authorize('storefront.manage');
        $page = $pages->editable($collection, App::getLocale());
        abort_if($page === null, 404);

        return Inertia::render('Storefront::Design/HomeBlocks', [
            'types' => array_values(array_map(fn (StorefrontBlock $type): array => [
                'type' => $type->type(), 'label' => $type->label(),
                'fields' => array_map(fn (FieldDefinition $field): array => $field->toArray(), $type->fields()),
            ], $blocks->types())),
            'blocks' => $page['blocks'],
            'configured' => $page['blocks'] !== [],
            'previewUrl' => $page['status'] === 'active' ? route('storefront.collection', $page['slugs'][StoreLocale::default()]) : null,
            'collectionName' => $page['name'],
            'collectionUrl' => Gate::allows('catalog.manage') ? route('admin.catalog.collections.edit', $collection) : null,
        ]);
    }

    public function update(Request $request, int $collection, CollectionPages $pages, PageBlocks $blocks, AuditLogger $audit): RedirectResponse
    {
        Gate::authorize('storefront.manage');
        $input = $request->validate([
            'blocks' => ['present', 'array'],
            'blocks.*.type' => ['required', 'string', 'max:64'],
            'blocks.*.zone' => ['required', Rule::in(PageBlocks::COLLECTION_ZONES)],
            'blocks.*.config' => ['nullable', 'array'],
        ]);
        $submitted = array_values($input['blocks']);
        $hasBanner = false;
        foreach ($submitted as $index => $block) {
            if ($block['zone'] !== 'banner') {
                continue;
            }
            if ($hasBanner || $block['type'] !== 'hero') {
                throw ValidationException::withMessages(["blocks.{$index}.zone" => __('storefront::messages.collection_banner_rule')]);
            }
            $hasBanner = true;
        }
        ['blocks' => $clean, 'media_ids' => $mediaIds] = $blocks->validate($submitted);
        foreach ($clean as $index => &$block) {
            $block['zone'] = $submitted[$index]['zone'];
        }
        unset($block);

        $pages->save($collection, $clean, $mediaIds);
        $audit->record('storefront.collection_blocks_updated', 'collection', $collection, ['count' => count($clean)]);

        return back()->with('success', __('storefront::messages.collection_page_saved'));
    }
}
