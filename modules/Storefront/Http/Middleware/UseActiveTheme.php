<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Modules\Extension\Application\Storefront\PluginViews;
use Modules\Storefront\Application\Theme\Theme;
use Modules\Storefront\Application\Theme\Themes;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trỏ namespace view `theme::` tới chuỗi theme đang hoạt động (theme con → cha → vani-base) cho request này;
 * view storefront của plugin được tìm trước ở `<theme>/plugins/<slug>/` rồi mới tới view gốc của plugin.
 */
final class UseActiveTheme
{
    public function __construct(
        private readonly Themes $themes,
        private readonly PluginViews $pluginViews,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $chain = $this->themes->chain();
        View::replaceNamespace('theme', array_map(fn (Theme $theme): string => $theme->viewsPath(), $chain));
        // View của plugin: override trong theme (con → cha) trước, rồi view gốc của plugin.
        foreach ($this->pluginViews->all() as $namespace => $plugin) {
            View::replaceNamespace($namespace, [
                ...array_map(fn (Theme $theme): string => "{$theme->path}/plugins/{$plugin['slug']}", $chain),
                $plugin['path'],
            ]);
        }
        // Finder nhớ đường dẫn view đã tìm; theme có thể đổi giữa các request cùng tiến trình (Octane, test).
        View::getFinder()->flush();
        View::share('themeTokens', $this->themes->tokens());

        return $next($request);
    }
}
