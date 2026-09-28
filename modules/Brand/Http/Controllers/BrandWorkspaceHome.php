<?php

declare(strict_types=1);

namespace Modules\Brand\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Brand\Contracts\BrandDirectory;
use Modules\Shared\Context\CurrentContext;

/**
 * Trang vào một khu vực brand workspace: một brand thì vào thẳng, nhiều brand thì cho chọn.
 */
final class BrandWorkspaceHome
{
    public function __construct(
        private readonly BrandDirectory $brands,
        private readonly CurrentContext $context,
    ) {}

    public function respond(string $routeName, string $title, string $subtitle): Response|RedirectResponse
    {
        $accessible = $this->brands->list($this->context->brandIds());

        if (count($accessible) === 1) {
            return redirect()->route($routeName, ['brand' => $accessible[0]->slug]);
        }

        return Inertia::render('BrandPicker', [
            'title' => $title,
            'subtitle' => $subtitle,
            'brands' => array_map(fn ($brand): array => ['name' => $brand->name, 'url' => route($routeName, ['brand' => $brand->slug])], $accessible),
        ]);
    }
}
