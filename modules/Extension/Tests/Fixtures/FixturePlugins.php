<?php

declare(strict_types=1);

namespace Modules\Extension\Tests\Fixtures;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Filesystem\Filesystem;
use Modules\Customer\Events\CustomerRegistered;
use Modules\Extension\Application\Plugins\ManifestRepository;
use Modules\Extension\Application\Plugins\PluginStateCache;
use Modules\Extension\PluginServiceProvider;
use Modules\Payment\Events\PaymentCaptured;
use Modules\Shared\Context\CurrentContext;

/**
 * Plugin giả cho test: ghi vanishop.json vào thư mục tạm và trỏ Extension tới đó.
 */
final class FixturePlugins
{
    public static function install(array $plugins): string
    {
        $root = storage_path('framework/testing/plugins-'.uniqid());
        $files = new Filesystem;

        foreach ($plugins as $directory => $manifest) {
            $files->ensureDirectoryExists("{$root}/{$directory}");
            $files->put("{$root}/{$directory}/vanishop.json", json_encode($manifest + [
                'name' => ['vi' => $manifest['id']],
                'version' => '1.0.0',
                'kind' => 'business',
                'provider' => GreetingPluginProvider::class,
                'requires' => ['vanishop' => '^0.2'],
                'scopes' => ['owner', 'brand', 'channel'],
            ], JSON_PRETTY_PRINT));
        }

        app()->instance(ManifestRepository::class, new ManifestRepository($root));
        app()->instance(PluginStateCache::class, new PluginStateCache($files, "{$root}/cache.php"));

        return $root;
    }

    public static function withMigration(string $root, string $directory): void
    {
        $files = new Filesystem;
        $files->ensureDirectoryExists("{$root}/{$directory}/Database/migrations");
        $files->put("{$root}/{$directory}/Database/migrations/2026_01_01_000000_create_plg_fixture_items.php", <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plg_fixture_items', fn (Blueprint $table) => $table->id());
    }

    public function down(): void
    {
        Schema::dropIfExists('plg_fixture_items');
    }
};
PHP);
    }
}

/**
 * Provider giả: thêm lời chào qua hook test.greeting.
 */
final class GreetingPluginProvider extends PluginServiceProvider
{
    public static string $id = 'fixture.greeting';

    protected function pluginId(): string
    {
        return self::$id;
    }

    public function boot(): void
    {
        $this->onFilter('test.greeting', fn (string $greeting): string => $greeting.' + '.self::$id);
    }
}

/**
 * Extension point giả: Core đăng ký implementation mặc định bằng `Extensions::tag()`,
 * plugin đóng góp bằng `PluginServiceProvider::contribute()`.
 */
interface FixtureExtension
{
    public function code(): string;
}

final class FixtureCoreExtension implements FixtureExtension
{
    public function code(): string
    {
        return 'fixture.core';
    }
}

final class FixturePluginExtension implements FixtureExtension
{
    public function code(): string
    {
        return 'fixture.plugin';
    }
}

/**
 * Provider giả: đóng góp implementation cho `ContributingPluginProvider::TAG` (tag giả của Core).
 */
final class ContributingPluginProvider extends PluginServiceProvider
{
    public const TAG = 'test.scoped_extensions';

    protected function pluginId(): string
    {
        return 'fixture.extensions';
    }

    public function boot(): void
    {
        $this->contribute(self::TAG, FixturePluginExtension::class);
    }
}

/**
 * Plugin nghe domain event qua onEvent(): ghi lại event nhận được và phạm vi brand lúc chạy.
 */
final class EventListeningPluginProvider extends PluginServiceProvider
{
    /** @var list<array{event: string, brand_ids: list<int>|null}> */
    public static array $received = [];

    public static bool $explode = false;

    protected function pluginId(): string
    {
        return 'fixture.events';
    }

    public function boot(): void
    {
        $record = function (object $event): void {
            if (self::$explode) {
                throw new \RuntimeException('plugin listener lỗi');
            }
            self::$received[] = ['event' => $event::class, 'brand_ids' => app(CurrentContext::class)->brandIds()];
        };

        $this->onEvent(PaymentCaptured::class, $record);
        $this->onEvent(CustomerRegistered::class, $record);
    }
}

final class SchedulingPluginProvider extends PluginServiceProvider
{
    protected function pluginId(): string
    {
        return 'fixture.scheduling';
    }

    public function boot(): void
    {
        $this->schedule(fn (Schedule $schedule) => $schedule->command('inspire')->hourly());
    }
}
