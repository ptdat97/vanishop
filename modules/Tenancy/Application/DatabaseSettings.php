<?php

declare(strict_types=1);

namespace Modules\Tenancy\Application;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Shared\Contracts\PageCacheTags;
use Modules\Tenancy\Contracts\Data\SettingDefinition;
use Modules\Tenancy\Contracts\Settings;
use Modules\Tenancy\Events\SettingChanged;
use Modules\Tenancy\Persistence\Models\SettingRecord;
use Throwable;

/**
 * Nạp mỗi namespace một lần mỗi request/job (scoped) rồi tra trong bộ nhớ. Dòng của namespace được cache ở store mặc định
 * theo khoá có phiên bản (`vani:settings:{phiên bản}:{namespace}`) — request sau không truy vấn bảng `settings`. Đổi cài đặt
 * (`set`/`forget`) hoặc chạy migration thay phiên bản **sau commit**, nên request khác không cache lại giá trị chưa commit;
 * chính request đang đổi đọc thẳng DB cho namespace đó tới hết request. Giá trị bí mật nằm trong cache ở dạng đã mã hoá.
 * Cache lỗi (vd. chưa có bảng lúc cài đặt) → đọc thẳng DB.
 */
final class DatabaseSettings implements Settings
{
    private const VERSION_KEY = 'vani:settings:version';

    /** Lưới an toàn: khoá phiên bản cũ tự hết hạn. */
    private const TTL_SECONDS = 86400;

    /** @var array<string, array<string, mixed>> namespace => key => value */
    private array $loaded = [];

    /** @var array<string, true> namespace đã đổi trong request/job này — đọc thẳng DB */
    private array $changed = [];

    public function __construct(private readonly SettingDefinitions $definitions, private readonly Cache $cache, private readonly PageCacheTags $pageTags) {}

    /**
     * Bỏ mọi namespace đang cache (đổi phiên bản). Gọi sau khi dữ liệu bảng `settings` đổi ngoài lớp này (migration).
     */
    public static function invalidate(Cache $cache): void
    {
        try {
            $cache->forever(self::VERSION_KEY, Str::random(16));
        } catch (Throwable) {
            // Chưa có store (lúc cài đặt) — không có gì để bỏ.
        }
    }

    public function get(string $namespace, string $key, mixed $default = null): mixed
    {
        $values = $this->load($namespace);

        if (array_key_exists($key, $values)) {
            return $values[$key];
        }

        return $this->definitions->find($namespace, $key)?->default ?? $default;
    }

    public function set(string $namespace, string $key, mixed $value): void
    {
        $secret = $this->definitions->find($namespace, $key)?->type === 'secret';
        $json = (string) json_encode($value, JSON_UNESCAPED_UNICODE);

        SettingRecord::query()->updateOrCreate(
            ['namespace' => $namespace, 'key' => $key],
            ['value' => $secret ? Crypt::encryptString($json) : $json, 'encrypted' => $secret],
        );
        $this->changed($namespace);
        // Không gửi giá trị cấu hình bí mật qua event.
        event(new SettingChanged($namespace, $key, $secret ? null : $value));
    }

    public function forget(string $namespace, string $key): void
    {
        SettingRecord::query()->where(['namespace' => $namespace, 'key' => $key])->delete();
        $this->changed($namespace);
        event(new SettingChanged($namespace, $key, null));
    }

    public function explicit(string $namespace): array
    {
        return $this->load($namespace);
    }

    public function define(SettingDefinition $definition): void
    {
        $this->definitions->add($definition);
    }

    public function definitions(?string $namespace = null): array
    {
        return $this->definitions->all($namespace);
    }

    /**
     * @return array<string, mixed>
     */
    private function load(string $namespace): array
    {
        // Trang công khai dùng cấu hình namespace này → cache trang hết hiệu lực khi cấu hình đổi (ADR-051).
        $this->pageTags->add('settings:'.$namespace);
        if (isset($this->loaded[$namespace])) {
            return $this->loaded[$namespace];
        }

        $values = [];
        foreach (isset($this->changed[$namespace]) ? $this->rows($namespace) : $this->cachedRows($namespace) as $row) {
            $json = $row['encrypted'] ? Crypt::decryptString($row['value']) : $row['value'];
            $values[$row['key']] = json_decode($json, true);
        }

        return $this->loaded[$namespace] = $values;
    }

    /**
     * @return list<array{key: string, value: string, encrypted: bool}>
     */
    private function cachedRows(string $namespace): array
    {
        try {
            $version = $this->cache->get(self::VERSION_KEY);
            if (! is_string($version)) {
                $this->cache->add(self::VERSION_KEY, Str::random(16));
                $version = (string) $this->cache->get(self::VERSION_KEY);
            }

            return $this->cache->remember("vani:settings:{$version}:{$namespace}", self::TTL_SECONDS, fn (): array => $this->rows($namespace));
        } catch (Throwable) {
            return $this->rows($namespace);
        }
    }

    /**
     * @return list<array{key: string, value: string, encrypted: bool}>
     */
    private function rows(string $namespace): array
    {
        return SettingRecord::query()->where('namespace', $namespace)->get(['key', 'value', 'encrypted'])
            ->map(fn (SettingRecord $row): array => ['key' => (string) $row->key, 'value' => (string) $row->value, 'encrypted' => (bool) $row->encrypted])
            ->values()->all();
    }

    private function changed(string $namespace): void
    {
        unset($this->loaded[$namespace]);
        $this->changed[$namespace] = true;
        DB::afterCommit(fn () => self::invalidate($this->cache));
    }
}
