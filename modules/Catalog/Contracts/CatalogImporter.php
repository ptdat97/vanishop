<?php

declare(strict_types=1);

namespace Modules\Catalog\Contracts;

use Modules\Catalog\Contracts\Data\ImportedProduct;
use Modules\Catalog\Contracts\Data\ProductImport;

/**
 * Service contract (0.3.27): nhập sản phẩm từ nguồn ngoài (plugin dữ liệu demo, ERP qua connector) mà không chạm tầng
 * nội bộ Catalog. Idempotent theo `styleCode`: sản phẩm đã có thì chỉ bổ sung màu/size/variant còn thiếu và ảnh cho màu
 * chưa có ảnh — không ghi đè nội dung nhân viên đã sửa. Mọi thay đổi có audit như thao tác Admin.
 */
interface CatalogImporter
{
    public function upsertProduct(ProductImport $import): ImportedProduct;
}
