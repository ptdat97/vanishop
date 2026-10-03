<?php

declare(strict_types=1);

namespace Modules\Storefront\Contracts;

/**
 * Extension point (tag `vani.storefront.sitemap`, 0.3.14): URL công khai của plugin (trang nội dung, bài viết…) đưa
 * vào /sitemap.xml. Core gọi khi dựng sitemap (cache 1 giờ), lỗi → bỏ phần của plugin đó.
 */
interface SitemapProvider
{
    public const TAG = 'vani.storefront.sitemap';

    /**
     * @return list<string> URL tuyệt đối, tối đa `$limit`
     */
    public function urls(int $limit): array;
}
