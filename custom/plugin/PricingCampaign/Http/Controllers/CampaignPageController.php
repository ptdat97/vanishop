<?php

declare(strict_types=1);

namespace Plugin\PricingCampaign\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Plugin\PricingCampaign\Infrastructure\CampaignPresenter;
use Plugin\PricingCampaign\PricingCampaignServiceProvider;

/** Trang Ưu đãi (`/uu-dai`, `/en/deals`) và Storefront API của chiến dịch giá. */
final class CampaignPageController
{
    public function __invoke(Request $request, CampaignPresenter $presenter): View
    {
        $status = $this->status($request);
        $sort = $this->sort($request);

        return view('vani-pricing-campaign::page', [
            'campaigns' => $presenter->listing(App::getLocale(), now()->getTimestamp(), $status, $sort),
            'status' => $status ?? 'all',
            'sort' => $sort,
            'scriptSrc' => vani_asset(PricingCampaignServiceProvider::ID, 'campaigns.js'),
        ]);
    }

    public function apiIndex(Request $request, CampaignPresenter $presenter): JsonResponse
    {
        return response()->json([
            'data' => $presenter->listing(App::getLocale(), now()->getTimestamp(), $this->status($request), $this->sort($request)),
        ]);
    }

    private function status(Request $request): ?string
    {
        $status = (string) $request->query('status', '');

        return in_array($status, ['running', 'upcoming'], true) ? $status : null;
    }

    private function sort(Request $request): string
    {
        $sort = (string) $request->query('sort', 'default');

        return in_array($sort, ['default', 'ending_soon', 'low_stock'], true) ? $sort : 'default';
    }

    public function product(Request $request, CampaignPresenter $presenter, int $product): JsonResponse
    {
        $validated = $request->validate(['campaign_id' => ['sometimes', 'integer', 'min:1']]);

        return response()->json(['data' => $presenter->forProduct(
            $product, App::getLocale(), now()->getTimestamp(), campaignId: isset($validated['campaign_id']) ? (int) $validated['campaign_id'] : null,
        )])
            ->header('Cache-Control', 'no-store, private');
    }
}
