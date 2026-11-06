<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
 * 0.3.90: khối trang chủ chỉ còn chọn ảnh từ Thư viện ảnh. Cấu hình đã lưu có URL ảnh nhập tay → đổi sang id ảnh khi URL
 * trỏ về ảnh của Thư viện ảnh (bản thu nhỏ /cache/media/…/{checksum}-… hoặc file gốc của disk media) và khai báo ảnh
 * đang dùng (mediables `storefront.home`). URL ngoài (CDN khác) giữ nguyên — khối vẫn hiện như cũ, nhân viên chọn lại ảnh
 * khi sửa.
 */
return new class extends Migration
{
    /** Loại khối → [khoá URL cũ → khoá id ảnh]. */
    private const FIELDS = [
        'hero' => ['image_url' => 'image'],
        'extras_video_hero' => ['poster_url' => 'poster'],
        'extras_slideshow' => ['slide_1_image' => 'slide_1_media', 'slide_2_image' => 'slide_2_media', 'slide_3_image' => 'slide_3_media', 'slide_4_image' => 'slide_4_media', 'slide_5_image' => 'slide_5_media'],
        'extras_banner_grid' => ['cell_1_image' => 'cell_1_media', 'cell_2_image' => 'cell_2_media', 'cell_3_image' => 'cell_3_media', 'cell_4_image' => 'cell_4_media'],
    ];

    public function up(): void
    {
        $row = DB::table('settings')->where('namespace', 'core')->where('key', 'storefront.home_blocks')->first();
        $blocks = $row === null ? null : json_decode((string) $row->value, true);
        if (! is_array($blocks)) {
            return;
        }

        $mediaIds = [];
        foreach ($blocks as $index => $block) {
            $config = (array) ($block['config'] ?? []);
            foreach (self::FIELDS[$block['type'] ?? ''] ?? [] as $urlKey => $mediaKey) {
                if (is_numeric($config[$mediaKey] ?? null)) {
                    $mediaIds[] = (int) $config[$mediaKey];

                    continue;
                }
                $id = $this->mediaIdFor((string) ($config[$urlKey] ?? ''));
                if ($id !== null) {
                    $config[$mediaKey] = $id;
                    unset($config[$urlKey]);
                    $mediaIds[] = $id;
                }
            }
            $blocks[$index]['config'] = $config;
        }

        DB::table('settings')->where('id', $row->id)->update(['value' => json_encode(array_values($blocks), JSON_UNESCAPED_UNICODE), 'updated_at' => now()]);
        DB::table('mediables')->where(['mediable_type' => 'storefront.home', 'mediable_id' => 1, 'role' => 'blocks'])->delete();
        foreach (array_values(array_unique($mediaIds)) as $position => $id) {
            DB::table('mediables')->insert(['media_id' => $id, 'mediable_type' => 'storefront.home', 'mediable_id' => 1, 'role' => 'blocks', 'position' => $position, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // Không khôi phục URL nhập tay.
    }

    private function mediaIdFor(string $url): ?int
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }
        if (preg_match('#/cache/media/[0-9a-f]{2}/([0-9a-f]{64})-#', $url, $match) === 1) {
            $id = DB::table('media')->where('checksum', $match[1])->value('id');

            return $id === null ? null : (int) $id;
        }
        $path = ltrim((string) parse_url($url, PHP_URL_PATH), '/');
        $path = (string) preg_replace('#^storage/#', '', $path);
        $id = $path === '' ? null : DB::table('media')->where('path', $path)->value('id');

        return $id === null ? null : (int) $id;
    }
};
