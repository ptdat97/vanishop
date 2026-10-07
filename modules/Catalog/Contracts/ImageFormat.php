<?php

declare(strict_types=1);

namespace Modules\Catalog\Contracts;

use Intervention\Image\Interfaces\EncoderInterface;

/**
 * Extension point (0.3.28): định dạng đầu ra của ảnh thu nhỏ trong public/cache. Core không đăng ký implementation nào
 * (giữ định dạng gốc: jpg → jpg, png → png); plugin đóng góp qua `contribute(ImageFormat::TAG, …)`, vd. vani.media-webp.
 * Nhiều plugin cùng hỗ trợ một định dạng nguồn → plugin đăng ký trước thắng.
 *
 * Đổi định dạng làm đổi URL ảnh (đuôi file); bản cache định dạng cũ vẫn nằm trên đĩa đến khi `vani:media:cache --clear`.
 */
interface ImageFormat
{
    public const TAG = 'catalog.image-format';

    public function code(): string;

    /**
     * Có xử lý ảnh nguồn kiểu MIME này không (vd. image/jpeg, image/png).
     */
    public function supports(string $sourceMimeType): bool;

    /**
     * Đuôi file đầu ra, chữ thường, không dấu chấm (vd. "webp"). Phải là một trong ImageCache::EXTENSIONS.
     */
    public function extension(): string;

    /**
     * @param  int<1, 100>  $quality  chất lượng cấu hình ở vanishop.media.cache.quality
     */
    public function encoder(int $quality): EncoderInterface;
}
