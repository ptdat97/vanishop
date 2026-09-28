<?php

use Illuminate\Support\Facades\File;
use Modules\Brand\Persistence\Models\Brand;
use Modules\Extension\Application\Plugins\PluginActivation;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Extension\Contracts\Extensions;
use Modules\Extension\Tests\Fixtures\ContributingPluginProvider;
use Modules\Extension\Tests\Fixtures\FixtureCoreExtension;
use Modules\Extension\Tests\Fixtures\FixturePluginExtension;
use Modules\Extension\Tests\Fixtures\FixturePlugins;
use Modules\Shared\Context\Actor;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

require_once __DIR__.'/../Fixtures/FixturePlugins.php';

beforeEach(function () {
    $this->root = FixturePlugins::install(['Extensions' => ['id' => 'fixture.extensions']]);
    $this->plugins = app(PluginManager::class);
    app(CurrentContext::class)->set(ContextScope::system('test'));

    // Implementation mặc định của Core cho extension point giả.
    app(Extensions::class)->tag([FixtureCoreExtension::class], ContributingPluginProvider::TAG);
    $this->app->register(ContributingPluginProvider::class);
});

afterEach(function () {
    File::deleteDirectory($this->root);
});

/**
 * @param  list<int>  $brandIds
 * @return list<string>
 */
function extensionCodes(array $brandIds): array
{
    return app(CurrentContext::class)->runAs(new ContextScope(Actor::guest(), brandIds: $brandIds), function (): array {
        app(PluginActivation::class)->flush();

        return array_map(fn (object $implementation): string => $implementation->code(), app(Extensions::class)->tagged(ContributingPluginProvider::TAG));
    });
}

it('chỉ trả implementation của plugin đang bật trong phạm vi hiện tại', function () {
    [$lumiere, $urbanx] = Brand::factory()->count(2)->create();

    $this->plugins->install('fixture.extensions');
    $this->plugins->enable('fixture.extensions', 'brand', $lumiere->id);

    expect(extensionCodes([$lumiere->id]))->toBe(['fixture.core', 'fixture.plugin'])
        ->and(extensionCodes([$urbanx->id]))->toBe(['fixture.core']);

    $this->plugins->disable('fixture.extensions');

    expect(extensionCodes([$lumiere->id]))->toBe(['fixture.core']);
});

it('bật ở scope owner thì implementation của plugin có mặt ở mọi brand', function () {
    [$lumiere, $urbanx] = Brand::factory()->count(2)->create();

    $this->plugins->install('fixture.extensions');
    $this->plugins->enable('fixture.extensions');

    expect(extensionCodes([$lumiere->id]))->toBe(['fixture.core', 'fixture.plugin'])
        ->and(extensionCodes([$urbanx->id]))->toBe(['fixture.core', 'fixture.plugin']);
});

it('ownerOf phân biệt implementation của Core và của plugin', function () {
    $this->plugins->install('fixture.extensions');
    $this->plugins->enable('fixture.extensions');

    $extensions = app(Extensions::class);

    expect($extensions->ownerOf(new FixturePluginExtension))->toBe('fixture.extensions')
        ->and($extensions->ownerOf(new FixtureCoreExtension))->toBeNull();
});
