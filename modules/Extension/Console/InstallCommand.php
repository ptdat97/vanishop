<?php

declare(strict_types=1);

namespace Modules\Extension\Console;

use Illuminate\Console\Command;
use Modules\Extension\Application\Plugins\ManifestRepository;
use Modules\Extension\Application\Plugins\PluginDoctor;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Extension\Application\Plugins\PluginOperationFailed;

/**
 * Dựng hệ thống mới (ADR-029): migrate + cài/bật plugin hệ thống (`"bundled": true`). Chạy lại an toàn.
 */
final class InstallCommand extends Command
{
    protected $signature = 'vani:install {--no-migrate : Bỏ qua bước migrate}';

    protected $description = 'Cài đặt VaniShop: migrate và cài + bật plugin hệ thống (COD, chuyển khoản, phí giao, thuế)';

    public function handle(PluginManager $plugins, PluginDoctor $doctor, ManifestRepository $manifests): int
    {
        if (! $this->option('no-migrate')) {
            $this->call('migrate', ['--force' => true]);
        }

        try {
            $result = $plugins->installBundled();
        } catch (PluginOperationFailed $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info($result['installed'] === []
            ? 'Plugin hệ thống đã được cài từ trước.'
            : 'Đã cài + bật: '.implode(', ', $result['installed']).'.');

        // Provider của plugin vừa cài chưa được nạp ở tiến trình này: nạp để doctor thấy implementation của chúng.
        foreach ($result['installed'] as $id) {
            $provider = $manifests->find($id)?->provider;
            if ($provider !== null) {
                $this->laravel->register($provider);
            }
        }

        $errors = array_filter($doctor->diagnose(), fn (array $issue): bool => $issue['level'] === PluginDoctor::ERROR);
        foreach ($errors as $issue) {
            $this->warn("[{$issue['plugin']}] {$issue['code']}: {$issue['message']}");
        }

        $this->line('Tiếp theo: php artisan vani:staff:create-owner');

        return $errors === [] ? self::SUCCESS : self::FAILURE;
    }
}
