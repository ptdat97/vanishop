<?php

declare(strict_types=1);

namespace Modules\Storefront\Contracts;

/**
 * Service contract: trang storefront (Core hoặc plugin) khai báo URL tương ứng ở từng ngôn ngữ và ngôn ngữ đã có bản
 * dịch (ADR-038 §5–7). Layout theme sinh canonical, `hreflang`, `noindex` cho bản chưa dịch và nút chuyển ngôn ngữ
 * từ khai báo này. Trang không khai báo: URL suy ra từ route hiện tại, mọi ngôn ngữ có route coi như đã dịch.
 * Trạng thái gắn với request hiện tại (không sống qua request).
 */
interface PageSeo
{
    /**
     * @param  array<string, string>  $urls  mã ngôn ngữ => URL tuyệt đối của cùng nội dung
     * @param  list<string>  $translated  ngôn ngữ có bản dịch (được index, có hreflang)
     */
    public function alternates(array $urls, array $translated): void;

    /** Trang không index ở mọi ngôn ngữ (xem trước, trang giao dịch). */
    public function noindex(): void;
}
