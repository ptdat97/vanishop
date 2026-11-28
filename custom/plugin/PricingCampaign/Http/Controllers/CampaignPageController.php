<?php

declare(strict_types=1);

namespace Plugin\PricingCampaign\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;
use Plugin\PricingCampaign\Infrastructure\CampaignPresenter;
use Plugin\PricingCampaign\PricingCampaignServiceProvider;

/** Trang Ưu đãi (`/uu-dai`, `/en/deals`) và Storefront API của chiến dịch giá. */
final class CampaignPageController
{
    public function __construct(private readonly CampaignPresenter $presenter) {}

    public function __invoke(): View
    {
        return view('vani-pricing-campaign::page', [
            'campaigns' => $this->presenter->listing(App::getLocale(), now()->getTimestamp()),
            'scriptSrc' => vani_asset(PricingCampaignServiceProvider::ID, 'campaigns.js'),
        ]);
    }

    public function apiIndex(): JsonResponse
    {
        return response()->json(['data' => $this->presenter->listing(App::getLocale(), now()->getTimestamp())]);
    }

    public function product(int $product): JsonResponse
    {
        return response()->json(['data' => $this->presenter->forProduct($product, App::getLocale(), now()->getTimestamp())])
            ->header('Cache-Control', 'no-store, private');
    }
}
