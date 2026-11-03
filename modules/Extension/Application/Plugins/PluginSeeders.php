<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Plugins;

use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Extension\Domain\Plugin\PluginManifest;
use Throwable;

/**
 * Seeder của plugin (plugin-system §7): class trong `Database/Seeders/` của plugin, namespace theo provider
 * (`Plugin\VietQr\VietQrServiceProvider` → `Plugin\VietQr\Database\Seeders\*`), chạy theo thứ tự tên file. Mỗi seeder
 * chạy một lần cho mỗi lần cài (ghi `plugin_seeders`), trong transaction riêng. Cần thứ tự khác: một seeder gọi
 * `$this->call([...])`. Seeder phải idempotent (upsert) vì `vani:plugin:seed --rerun` chạy lại.
 */
final class PluginSeeders
{
    public const TABLE = 'plugin_seeders';

    public function __construct(private readonly Container $container) {}

    /**
     * @return list<class-string>
     */
    public function discover(PluginManifest $manifest): array
    {
        $files = glob($manifest->path.'/Database/Seeders/*.php') ?: [];
        sort($files);
        $namespace = substr($manifest->provider, 0, (int) strrpos($manifest->provider, '\\')).'\\Database\\Seeders\\';

        /** @var list<class-string> */
        return array_map(fn (string $file): string => $namespace.basename($file, '.php'), $files);
    }

    /**
     * @return list<array{seeder: string, ran_at: string|null}>
     */
    public function status(PluginManifest $manifest): array
    {
        $ran = DB::table(self::TABLE)->where('plugin_id', $manifest->id)->pluck('ran_at', 'seeder');

        return array_map(fn (string $seeder): array => ['seeder' => $seeder, 'ran_at' => $ran[$seeder] ?? null], $this->discover($manifest));
    }

    /**
     * @return list<class-string>
     */
    public function pending(PluginManifest $manifest): array
    {
        $ran = DB::table(self::TABLE)->where('plugin_id', $manifest->id)->pluck('seeder')->all();

        return array_values(array_diff($this->discover($manifest), $ran));
    }

    /**
     * Chạy seeder chưa chạy (hoặc tất cả khi `$rerun`). Seeder lỗi → dừng, seeder đã chạy trước đó vẫn được ghi nhận.
     *
     * @return list<class-string> seeder đã chạy
     *
     * @throws PluginOperationFailed
     */
    public function run(PluginManifest $manifest, bool $rerun = false): array
    {
        $ran = [];
        foreach ($rerun ? $this->discover($manifest) : $this->pending($manifest) as $class) {
            if (! class_exists($class) || ! is_subclass_of($class, Seeder::class)) {
                throw new PluginOperationFailed("Seeder [{$class}] của [{$manifest->id}] không tồn tại hoặc không kế thừa ".Seeder::class.'.');
            }

            try {
                DB::transaction(function () use ($manifest, $class): void {
                    /** @var Seeder $seeder */
                    $seeder = $this->container->make($class);
                    $seeder->setContainer($this->container)->__invoke();
                    DB::table(self::TABLE)->upsert([['plugin_id' => $manifest->id, 'seeder' => $class, 'ran_at' => now()]], ['plugin_id', 'seeder'], ['ran_at']);
                });
            } catch (Throwable $exception) {
                throw new PluginOperationFailed("Seeder [{$class}] của [{$manifest->id}] lỗi: {$exception->getMessage()}");
            }
            $ran[] = $class;
        }

        return $ran;
    }

    /** Xoá ghi nhận (gỡ --purge): cài lại sẽ seed lại từ đầu. */
    public function forget(string $pluginId): void
    {
        DB::table(self::TABLE)->where('plugin_id', $pluginId)->delete();
    }
}
