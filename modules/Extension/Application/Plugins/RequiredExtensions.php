<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Plugins;

use Modules\Extension\Contracts\Extensions;

/**
 * Kiểm tra extension point bắt buộc (ADR-029, commerce-kernel §5): luôn còn ít nhất một implementation có hiệu lực
 * (của Core hoặc của plugin đang bật).
 */
final class RequiredExtensions
{
    public function __construct(private readonly Extensions $extensions) {}

    /**
     * Extension point bắt buộc sẽ không còn implementation nếu tắt $pluginId.
     *
     * @param  list<string>  $enabledIds  plugin đang bật
     * @return list<string> nhãn extension point
     */
    public function brokenWithout(string $pluginId, array $enabledIds): array
    {
        $broken = [];
        foreach ($this->extensions->requirements() as $tag => $requirement) {
            $providers = $this->extensions->providers($tag);
            if (! in_array($pluginId, $providers, true)) {
                continue;
            }

            $remaining = array_filter($providers, fn (?string $provider): bool => $provider === null || ($provider !== $pluginId && in_array($provider, $enabledIds, true)));
            if ($remaining === []) {
                $broken[] = $requirement['label'];
            }
        }

        return $broken;
    }

    /**
     * Extension point bắt buộc hiện không có implementation nào có hiệu lực.
     *
     * @param  list<string>  $enabledIds
     * @return array<string, string> tag => nhãn
     */
    public function missing(array $enabledIds): array
    {
        $missing = [];
        foreach ($this->extensions->requirements() as $tag => $requirement) {
            $active = array_filter($this->extensions->providers($tag), fn (?string $provider): bool => $provider === null || in_array($provider, $enabledIds, true));
            if ($active === []) {
                $missing[$tag] = $requirement['label'];
            }
        }

        return $missing;
    }
}
