<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Plugins;

use Illuminate\Support\Facades\File;
use Modules\Extension\Domain\Plugin\PluginManifest;
use RuntimeException;

/**
 * Tài nguyên tĩnh của plugin (reference-parity §3.4-2): copy thư mục `assets` của manifest sang public/plugins/<id>/ với
 * tên file có hash nội dung (web server trả tĩnh, cache dài hạn), kèm `manifest.json` (đường dẫn gốc → đường dẫn có hash).
 * Không phục vụ qua route PHP. Publish khi bật/nâng plugin và khi deploy (`vani:plugin:assets`); gỡ plugin thì xoá.
 */
final class PluginAssets
{
    private const MANIFEST = 'manifest.json';

    /** Đuôi file được publish (tài nguyên tĩnh cho trình duyệt; không publish PHP, cấu hình…). */
    private const EXTENSIONS = ['css', 'js', 'mjs', 'map', 'json', 'svg', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'avif', 'ico', 'woff', 'woff2', 'ttf', 'otf'];

    /** @var array<string, array<string, string>> plugin id => manifest đã đọc (trong request) */
    private array $loaded = [];

    /**
     * @param  string|null  $baseUrl  URL công khai của $publicPath; null = asset('plugins')
     */
    public function __construct(
        private readonly string $publicPath,
        private readonly ?string $baseUrl = null,
    ) {}

    /**
     * @return int số file đã publish
     */
    public function publish(PluginManifest $manifest): int
    {
        $source = $manifest->assets === null ? null : $manifest->path.'/'.$manifest->assets;
        if ($source === null || ! is_dir($source)) {
            $this->remove($manifest->id);

            return 0;
        }

        // Chép vào thư mục tạm riêng của tiến trình rồi đổi tên: hai lệnh publish song song (deploy, test chạy song song)
        // không xoá file của nhau giữa chừng.
        $final = $this->directory($manifest->id);
        $target = $final.'.tmp-'.bin2hex(random_bytes(6));
        $map = [];
        foreach (File::allFiles($source) as $file) {
            $extension = strtolower($file->getExtension());
            if (! in_array($extension, self::EXTENSIONS, true)) {
                continue;
            }
            $relative = str_replace('\\', '/', $file->getRelativePathname());
            $hashed = substr($relative, 0, -strlen($extension) - 1).'.'.substr(hash_file('sha256', $file->getPathname()), 0, 10).'.'.$extension;
            self::ensureDirectory(dirname($target.'/'.$hashed));
            File::copy($file->getPathname(), $target.'/'.$hashed);
            $map[$relative] = $hashed;
        }
        ksort($map);
        self::ensureDirectory($target);
        File::put($target.'/'.self::MANIFEST, (string) json_encode($map, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        File::deleteDirectory($final);
        // Tiến trình khác vừa publish xong (cùng nội dung) → giữ bản của nó, bỏ bản tạm.
        if (! @rename($target, $final)) {
            File::deleteDirectory($target);
        }
        unset($this->loaded[$manifest->id]);

        return count($map);
    }

    /**
     * Tạo thư mục, chịu được việc tiến trình khác vừa tạo cùng thư mục (hai lệnh publish chạy song song: kiểm "chưa có"
     * rồi mkdir của File::ensureDirectoryExists báo "File exists").
     */
    private static function ensureDirectory(string $path): void
    {
        if (! is_dir($path) && ! @mkdir($path, 0755, true) && ! is_dir($path)) {
            throw new RuntimeException("Không tạo được thư mục {$path}.");
        }
    }

    public function remove(string $pluginId): void
    {
        File::deleteDirectory($this->directory($pluginId));
        unset($this->loaded[$pluginId]);
    }

    /**
     * Xoá thư mục publish của plugin không còn trong danh sách (đã gỡ, đổi id).
     *
     * @param  list<string>  $keep
     * @return list<string> plugin id đã xoá
     */
    public function prune(array $keep): array
    {
        $removed = [];
        foreach (is_dir($this->publicPath) ? File::directories($this->publicPath) : [] as $directory) {
            $id = basename($directory);
            if (! in_array($id, $keep, true)) {
                File::deleteDirectory($directory);
                $removed[] = $id;
            }
        }

        return $removed;
    }

    public function url(string $pluginId, string $path): ?string
    {
        $map = $this->loaded[$pluginId] ??= $this->read($pluginId);
        $hashed = $map[ltrim($path, '/')] ?? null;

        return $hashed === null ? null : rtrim($this->baseUrl ?? asset('plugins'), '/').'/'.rawurlencode($pluginId).'/'.implode('/', array_map('rawurlencode', explode('/', $hashed)));
    }

    /**
     * @return array<string, string>
     */
    private function read(string $pluginId): array
    {
        $file = $this->directory($pluginId).'/'.self::MANIFEST;
        $map = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;

        return is_array($map) ? array_map('strval', $map) : [];
    }

    private function directory(string $pluginId): string
    {
        return rtrim($this->publicPath, '/').'/'.$pluginId;
    }
}
