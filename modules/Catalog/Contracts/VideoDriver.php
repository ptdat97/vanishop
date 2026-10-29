<?php

declare(strict_types=1);

namespace Modules\Catalog\Contracts;

use Modules\Catalog\Contracts\Data\VideoThumbnail;

/**
 * Extension point (0.3.46): nền tảng video cho video sản phẩm (YouTube, Vimeo, Bunny Stream…). Core không đăng ký
 * implementation nào; plugin đóng góp qua `contribute(VideoDriver::TAG, …)`, vd. vani.video-youtube. Video của driver
 * đang tắt/gỡ vẫn nằm trong DB nhưng không hiện trên storefront và không thêm mới được.
 *
 * Phỏng theo VideoDriver của Vanilo (MIT, xem modules/Catalog/NOTICE); trả URL nhúng thay cho HTML nhúng để theme tự
 * dựng iframe (không in HTML do plugin sinh ra).
 */
interface VideoDriver
{
    public const TAG = 'catalog.video-driver';

    /**
     * Mã lưu ở cột videos.driver, chữ thường (vd. "youtube"). Không đổi sau khi đã có video.
     */
    public function code(): string;

    public function label(): string;

    /**
     * Hướng dẫn nhập cho Admin (vd. "Link YouTube hoặc ID video").
     */
    public function inputHint(): string;

    /**
     * Chuẩn hoá thứ Admin nhập (URL hoặc ID) thành định danh video trên nền tảng; null = không hợp lệ.
     */
    public function reference(string $input): ?string;

    public function watchUrl(string $reference): string;

    /**
     * URL https cho thuộc tính src của iframe; null = nền tảng không cho nhúng.
     */
    public function embedUrl(string $reference): ?string;

    public function thumbnail(string $reference): ?VideoThumbnail;
}
