<?php

declare(strict_types=1);

namespace Plugin\Lookbook\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Modules\Shared\Support\StoreLocale;
use Modules\Storefront\Contracts\PageSeo;
use Plugin\Lookbook\Infrastructure\LookbookPresenter;
use Plugin\Lookbook\LookbookServiceProvider;
use Plugin\Lookbook\Persistence\Lookbook;

/**
 * /lookbook, /lookbook/{slug} (trang công khai, cache CDN được) và Storefront API cho headless. Lookbook tắt → 404.
 */
final class StorefrontController
{
    public function __construct(private readonly LookbookPresenter $presenter, private readonly PageSeo $seo) {}

    public function index(Request $request): View
    {
        $paginator = $this->query()->paginate($this->perPage())->withQueryString();

        return view('vani-lookbook::pages.index', [
            'lookbooks' => $this->presenter->summaries($paginator->getCollection()->all(), App::getLocale()),
            'paginator' => $paginator,
        ]);
    }

    public function show(string $slug): View
    {
        $lookbook = Lookbook::query()->visible()->with('images')->where('slug', $slug)->first() ?? abort(404);
        // Bản ngôn ngữ chưa dịch: hiện bản mặc định, noindex (ADR-038 §5).
        $urls = [];
        foreach (StoreLocale::supported() as $locale) {
            $urls[$locale] = vani_route('storefront.p.vani-lookbook.show', $slug, $locale);
        }
        $this->seo->alternates($urls, $lookbook->translatedLocales());

        return view('vani-lookbook::pages.show', ['lookbook' => $this->presenter->detail($lookbook, App::getLocale())]);
    }

    public function apiIndex(Request $request): JsonResponse
    {
        $paginator = $this->query()->paginate(min(48, max(1, (int) $request->query('per_page', $this->perPage()))));

        return response()->json([
            'data' => $this->presenter->summaries($paginator->getCollection()->all(), App::getLocale()),
            'meta' => ['page' => $paginator->currentPage(), 'per_page' => $paginator->perPage(), 'total' => $paginator->total()],
        ]);
    }

    public function apiShow(string $slug): JsonResponse
    {
        $lookbook = Lookbook::query()->visible()->with('images')->where('slug', $slug)->first() ?? abort(404);

        return response()->json(['data' => $this->presenter->detail($lookbook, App::getLocale())]);
    }

    /**
     * @return Builder<Lookbook>
     */
    private function query(): Builder
    {
        return Lookbook::query()->visible()->with('images')->orderBy('position')->orderByDesc('id');
    }

    private function perPage(): int
    {
        return max(6, min(48, (int) vani_setting(LookbookServiceProvider::ID, 'per_page', 12)));
    }
}
