<?php

declare(strict_types=1);

namespace Plugin\VideoYoutube\Infrastructure;

use Modules\Catalog\Contracts\Data\VideoThumbnail;
use Modules\Catalog\Contracts\VideoDriver;

/**
 * Định danh video YouTube là ID 11 ký tự. Nhận ID trần hoặc link watch, youtu.be, shorts, embed, live.
 * Phỏng theo driver Youtube của Vanilo (MIT, xem modules/Catalog/NOTICE).
 */
final class YoutubeDriver implements VideoDriver
{
    private const ID_PATTERN = '[A-Za-z0-9_-]{11}';

    public function code(): string
    {
        return 'youtube';
    }

    public function label(): string
    {
        return 'YouTube';
    }

    public function inputHint(): string
    {
        return 'Dán link YouTube (youtube.com/watch?v=…, youtu.be/…, Shorts) hoặc ID video 11 ký tự.';
    }

    public function reference(string $input): ?string
    {
        if (preg_match('/^'.self::ID_PATTERN.'$/', $input) === 1) {
            return $input;
        }

        $parts = parse_url($input);
        $host = strtolower(preg_replace('/^(www\.|m\.|music\.)/', '', $parts['host'] ?? ''));
        $path = $parts['path'] ?? '';

        $isYoutube = in_array($host, ['youtube.com', 'youtube-nocookie.com'], true);
        $candidate = match (true) {
            $host === 'youtu.be' => explode('/', ltrim($path, '/'))[0],
            $isYoutube && $path === '/watch' => $this->queryParameter($parts['query'] ?? '', 'v'),
            $isYoutube && preg_match('#^/(?:shorts|embed|live|v)/([^/]+)#', $path, $matches) === 1 => $matches[1],
            default => null,
        };

        return $candidate !== null && preg_match('/^'.self::ID_PATTERN.'$/', $candidate) === 1 ? $candidate : null;
    }

    public function watchUrl(string $reference): string
    {
        return 'https://www.youtube.com/watch?v='.rawurlencode($reference);
    }

    public function embedUrl(string $reference): string
    {
        return 'https://www.youtube-nocookie.com/embed/'.rawurlencode($reference);
    }

    public function thumbnail(string $reference): VideoThumbnail
    {
        return new VideoThumbnail('https://i.ytimg.com/vi/'.rawurlencode($reference).'/hqdefault.jpg', 480, 360);
    }

    private function queryParameter(string $query, string $name): ?string
    {
        parse_str($query, $parameters);

        return is_string($parameters[$name] ?? null) ? $parameters[$name] : null;
    }
}
