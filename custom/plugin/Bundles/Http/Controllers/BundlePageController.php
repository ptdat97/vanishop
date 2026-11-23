<?php

declare(strict_types=1);

namespace Plugin\Bundles\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\App;
use Plugin\Bundles\BundlesServiceProvider;
use Plugin\Bundles\Infrastructure\BundlePresenter;

final class BundlePageController
{
    public function __invoke(BundlePresenter $presenter): View
    {
        return view('vani-bundles::pages.index', [
            ...$presenter->listing(App::getLocale()),
            'scriptSrc' => vani_asset(BundlesServiceProvider::ID, 'bundles.js'),
        ]);
    }
}
