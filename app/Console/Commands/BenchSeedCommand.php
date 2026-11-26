<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Closure;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Dữ liệu quy mô lớn để đo hiệu năng DB ([db-refactor §2.2](docs/20-roadmap/db-refactor.md)): mặc định 100.000 sản phẩm,
 * 1.000.000 SKU (2 màu × 5 size), 5.000.000 đơn (2 dòng mỗi đơn), 500.000 khách. Sinh bằng `INSERT … SELECT` trên bảng
 * số thứ tự `bench_seq` (không qua model/service — nhanh, nhưng chỉ dùng để đo). Chỉ chạy trên MySQL, DB tên `*_bench`.
 *
 *   DB_DATABASE=vanishop_bench php artisan vani:bench:seed --fresh
 */
final class BenchSeedCommand extends Command
{
    protected $signature = 'vani:bench:seed
        {--fresh : migrate:fresh + vani:install trước khi sinh}
        {--continue : Chỉ sinh tiếp đơn từ lô 1 triệu cuối đã xong (lần chạy trước bị dừng)}
        {--products=100000 : Số sản phẩm (mỗi sản phẩm 10 SKU)}
        {--orders=5000000 : Số đơn (mỗi đơn 2 dòng)}
        {--customers=500000 : Số khách}';

    protected $description = 'Sinh dữ liệu quy mô lớn vào DB đo hiệu năng (*_bench, MySQL)';

    private const CHUNK = 250_000;

    /** @var list<array{0: string, 1: string, 2: string}> mã, tên, nhóm màu */
    private const COLORS = [
        ['TRG', 'Trắng', 'white'], ['NGA', 'Trắng ngà', 'white'], ['DEN', 'Đen', 'black'], ['XAM', 'Xám', 'grey'], ['GHI', 'Ghi', 'grey'],
        ['BE', 'Be', 'beige'], ['KEM', 'Kem', 'beige'], ['NAU', 'Nâu', 'brown'], ['CAF', 'Cà phê', 'brown'], ['DO', 'Đỏ', 'red'],
        ['DOD', 'Đỏ đô', 'red'], ['HONG', 'Hồng', 'pink'], ['HPA', 'Hồng phấn', 'pink'], ['CAM', 'Cam', 'orange'], ['VANG', 'Vàng', 'yellow'],
        ['XLA', 'Xanh lá', 'green'], ['REU', 'Xanh rêu', 'green'], ['XDU', 'Xanh dương', 'blue'], ['NAVY', 'Xanh navy', 'blue'], ['XNG', 'Xanh ngọc', 'blue'],
        ['TIM', 'Tím', 'purple'], ['OAI', 'Tím oải hương', 'purple'], ['BAC', 'Bạc', 'grey'], ['VDO', 'Vàng đồng', 'yellow'],
    ];

    private const SIZES = ['XS', 'S', 'M', 'L', 'XL', '2XL', '3XL', '4XL'];

    /** @var list<array{0: string, 1: string}> tên có dấu, không dấu */
    private const KINDS = [
        ['Áo sơ mi', 'ao so mi'], ['Áo thun', 'ao thun'], ['Quần jean', 'quan jean'], ['Đầm', 'dam'], ['Chân váy', 'chan vay'], ['Áo khoác', 'ao khoac'],
        ['Quần short', 'quan short'], ['Áo len', 'ao len'], ['Váy liền', 'vay lien'], ['Áo polo', 'ao polo'], ['Quần tây', 'quan tay'], ['Áo blazer', 'ao blazer'],
    ];

    /** @var list<array{0: string, 1: string}> */
    private const TRAITS = [
        ['lụa', 'lua'], ['cotton', 'cotton'], ['linen', 'linen'], ['dáng suông', 'dang suong'], ['xếp ly', 'xep ly'],
        ['oversize', 'oversize'], ['basic', 'basic'], ['cổ tròn', 'co tron'], ['kẻ sọc', 'ke soc'], ['họa tiết', 'hoa tiet'],
    ];

    public function handle(): int
    {
        $database = (string) DB::connection()->getDatabaseName();
        if (DB::getDriverName() !== 'mysql' || ! str_ends_with($database, '_bench')) {
            $this->error('Chỉ chạy trên MySQL với DB tên *_bench (đang: '.DB::getDriverName()." / {$database}).");

            return self::FAILURE;
        }
        $products = max(1, (int) $this->option('products'));
        $orders = max(0, (int) $this->option('orders'));
        $customers = max(1, (int) $this->option('customers'));
        set_time_limit(0);
        DB::disableQueryLog();

        if ($this->option('fresh')) {
            $this->step('migrate:fresh + vani:install', function (): void {
                Artisan::call('migrate:fresh', ['--force' => true]);
                Artisan::call('vani:install', ['--no-migrate' => true, '--no-interaction' => true]);
            });
        }

        DB::statement('SET SESSION foreign_key_checks = 0');
        DB::statement('SET SESSION unique_checks = 0');
        $now = now()->format('Y-m-d H:i:s');

        if ($this->option('continue')) {
            $done = intdiv((int) DB::table('order_lines')->max('order_id'), 1_000_000) * 1_000_000;
            DB::table('order_lines')->where('order_id', '>', $done)->delete();
            DB::table('orders')->where('id', '>', $done)->delete();
            $this->step('bảng số thứ tự', fn () => $this->sequence(max($products, $orders, $customers, 1_000_000)));
            $this->step('đơn từ '.($done + 1), fn () => $this->orders($orders, $products, $customers, $done));
            DB::statement('ANALYZE TABLE `orders`, `order_lines`');
            DB::statement('DROP TABLE IF EXISTS bench_seq, bench_color, bench_size');

            return self::SUCCESS;
        }

        $this->step('bảng số thứ tự', fn () => $this->sequence(max($products, $orders, $customers, 1_000_000)));
        $this->step('thương hiệu, danh mục, bộ sưu tập', fn () => $this->taxonomy($now));
        [$colorType, $sizeType] = $this->step('tuỳ chọn, thuộc tính', fn (): array => $this->optionCatalog($now));
        $this->step("{$products} sản phẩm", fn () => $this->products($products, $now));
        $this->step('SKU (×10), giá, tồn', fn () => $this->variants($products, $colorType, $sizeType, $now));
        $this->step('read model trang danh sách', fn () => $this->listing());
        $this->step("{$customers} khách", fn () => $this->customers($customers, $now));
        $this->step("{$orders} đơn (×2 dòng)", fn () => $this->orders($orders, $products, $customers));
        $this->step('ANALYZE TABLE', function (): void {
            foreach (['products', 'product_listing', 'product_facet_values', 'product_translations', 'category_product', 'collection_product', 'product_attribute_values', 'product_options', 'product_option_values', 'variants', 'variant_option_values', 'prices', 'stock_levels', 'customers', 'orders', 'order_lines'] as $table) {
                DB::statement("ANALYZE TABLE `{$table}`");
            }
        });
        DB::statement('DROP TABLE IF EXISTS bench_seq, bench_color, bench_size');

        return self::SUCCESS;
    }

    /**
     * @template T
     *
     * @param  Closure(): T  $work
     * @return T
     */
    private function step(string $label, Closure $work): mixed
    {
        $started = hrtime(true);
        $this->output->write("{$label} … ");
        $result = $work();
        $this->line(sprintf('%.1f s', (hrtime(true) - $started) / 1e9));

        return $result;
    }

    private function sequence(int $size): void
    {
        DB::statement('DROP TABLE IF EXISTS bench_seq');
        DB::statement('CREATE TABLE bench_seq (n INT UNSIGNED NOT NULL PRIMARY KEY)');
        DB::statement('INSERT INTO bench_seq (n) VALUES (0),(1),(2),(3),(4),(5),(6),(7),(8),(9)');
        for ($have = 10; $have < $size; $have *= 10) {
            DB::statement("INSERT INTO bench_seq (n) SELECT s.n + d.n * {$have} FROM bench_seq s CROSS JOIN (SELECT n FROM bench_seq WHERE n BETWEEN 1 AND 9) d WHERE s.n < {$have}");
        }
    }

    private function taxonomy(string $now): void
    {
        DB::statement("INSERT INTO brands (id, code, slug, name, status, position, lock_version, created_at, updated_at)
            SELECT n + 1, CONCAT('B', LPAD(n + 1, 2, '0')), CONCAT('thuong-hieu-', n + 1), CONCAT('Thương hiệu ', n + 1), 'active', n, 0, '{$now}', '{$now}'
            FROM bench_seq WHERE n < 50");
        DB::statement("INSERT INTO categories (id, parent_id, slug, path, depth, position, status, lock_version, created_at, updated_at)
            SELECT n + 1, NULL, CONCAT('nhom-', n + 1), CONCAT('/', n + 1, '/'), 1, n, 'active', 0, '{$now}', '{$now}' FROM bench_seq WHERE n < 20");
        DB::statement("INSERT INTO categories (id, parent_id, slug, path, depth, position, status, lock_version, created_at, updated_at)
            SELECT 21 + n, 1 + n DIV 10, CONCAT('loai-', 21 + n), CONCAT('/', 1 + n DIV 10, '/', 21 + n, '/'), 2, n % 10, 'active', 0, '{$now}', '{$now}' FROM bench_seq WHERE n < 200");
        DB::statement("INSERT INTO category_translations (category_id, locale, name) SELECT id, 'vi', CONCAT('Danh mục ', id) FROM categories");
        DB::statement("INSERT INTO collections (id, slug, status, position, lock_version, created_at, updated_at)
            SELECT n + 1, CONCAT('bst-', n + 1), 'active', n, 0, '{$now}', '{$now}' FROM bench_seq WHERE n < 20");
        DB::statement("INSERT INTO collection_translations (collection_id, locale, name) SELECT id, 'vi', CONCAT('Bộ sưu tập ', id) FROM collections");
    }

    /**
     * @return array{0: int, 1: int} id loại màu, id loại size
     */
    private function optionCatalog(string $now): array
    {
        $colorType = (int) DB::table('option_types')->where('code', 'color')->value('id');
        $sizeType = (int) DB::table('option_types')->where('code', 'size')->value('id');
        DB::statement('DROP TABLE IF EXISTS bench_color, bench_size');
        DB::statement('CREATE TABLE bench_color (idx INT UNSIGNED PRIMARY KEY, id BIGINT UNSIGNED NOT NULL)');
        DB::statement('CREATE TABLE bench_size (idx INT UNSIGNED PRIMARY KEY, id BIGINT UNSIGNED NOT NULL, code VARCHAR(8) NOT NULL)');
        foreach (self::COLORS as $index => [$code, $name, $family]) {
            $id = DB::table('option_values')->insertGetId(['option_type_id' => $colorType, 'code' => $code, 'family' => $family, 'filter_token' => $family, 'hex' => '#'.substr(md5($code), 0, 6), 'position' => $index, 'created_at' => $now, 'updated_at' => $now]);
            DB::table('option_value_translations')->insert(['option_value_id' => $id, 'locale' => 'vi', 'name' => $name]);
            DB::table('bench_color')->insert(['idx' => $index, 'id' => $id]);
        }
        foreach (self::SIZES as $index => $code) {
            $id = DB::table('option_values')->insertGetId(['option_type_id' => $sizeType, 'code' => $code, 'filter_token' => $code, 'group' => 'alpha', 'position' => $index, 'created_at' => $now, 'updated_at' => $now]);
            DB::table('option_value_translations')->insert(['option_value_id' => $id, 'locale' => 'vi', 'name' => $code]);
            DB::table('bench_size')->insert(['idx' => $index, 'id' => $id, 'code' => $code]);
        }

        foreach ([['chat-lieu', 'Chất liệu', 12], ['kieu-dang', 'Kiểu dáng', 6]] as $position => [$code, $name, $count]) {
            $attribute = DB::table('attributes')->insertGetId(['code' => $code, 'kind' => 'spec', 'input_type' => 'select', 'is_filterable' => true, 'position' => $position, 'lock_version' => 0, 'created_at' => $now, 'updated_at' => $now]);
            DB::table('attribute_translations')->insert(['attribute_id' => $attribute, 'locale' => 'vi', 'name' => $name]);
            for ($i = 1; $i <= $count; $i++) {
                $value = DB::table('attribute_values')->insertGetId(['attribute_id' => $attribute, 'code' => "{$code}-{$i}", 'position' => $i, 'created_at' => $now, 'updated_at' => $now]);
                DB::table('attribute_value_translations')->insert(['attribute_value_id' => $value, 'locale' => 'vi', 'label' => "{$name} {$i}"]);
            }
        }

        return [$colorType, $sizeType];
    }

    private function products(int $count, string $now): void
    {
        $kind = fn (int $column): string => "ELT(n % 12 + 1, '".implode("', '", array_column(self::KINDS, $column))."')";
        $trait = fn (int $column): string => "ELT((n DIV 12) % 10 + 1, '".implode("', '", array_column(self::TRAITS, $column))."')";
        $code = "CONCAT('BP', LPAD(n + 1, 6, '0'))";
        $material = (int) DB::table('attributes')->where('code', 'chat-lieu')->value('id');
        $shape = (int) DB::table('attributes')->where('code', 'kieu-dang')->value('id');
        $firstMaterial = (int) DB::table('attribute_values')->where('attribute_id', $material)->min('id');
        $firstShape = (int) DB::table('attribute_values')->where('attribute_id', $shape)->min('id');

        foreach ($this->ranges($count) as [$from, $to]) {
            $where = "n >= {$from} AND n < {$to}";
            // ~2 năm sản phẩm mới, 2% nháp, 2% lưu trữ, 1% hẹn giờ hiển thị trong tương lai.
            DB::statement("INSERT INTO products (id, brand_id, product_code, slug, status, track_inventory, published_from, published_to, primary_category_id, search_text, lock_version, created_at, updated_at)
                SELECT n + 1, 1 + n % 50, {$code}, LOWER({$code}),
                    CASE WHEN n % 50 = 0 THEN 'draft' WHEN n % 50 = 1 THEN 'archived' ELSE 'active' END, 1,
                    CASE WHEN n % 100 = 2 THEN '2030-01-01 00:00:00' ELSE NULL END, NULL,
                    21 + n % 200, CONCAT({$kind(1)}, ' ', {$trait(1)}, ' ', LOWER({$code})), 0,
                    TIMESTAMPADD(SECOND, n * 600, '2024-11-26 00:00:00'), TIMESTAMPADD(SECOND, n * 600, '2024-11-26 00:00:00')
                FROM bench_seq WHERE {$where}");
            DB::statement("INSERT INTO product_translations (product_id, locale, name, description)
                SELECT n + 1, 'vi', CONCAT({$kind(0)}, ' ', {$trait(0)}, ' ', {$code}), 'Mô tả sản phẩm đo hiệu năng.' FROM bench_seq WHERE {$where}");
            DB::statement("INSERT INTO category_product (category_id, product_id, position) SELECT 21 + n % 200, n + 1, n % 100 FROM bench_seq WHERE {$where}");
            DB::statement("INSERT INTO category_product (category_id, product_id, position) SELECT 21 + (n * 7 + 3) % 200, n + 1, n % 100 FROM bench_seq WHERE {$where} AND n % 10 < 3 AND (n * 7 + 3) % 200 <> n % 200");
            DB::statement("INSERT INTO collection_product (collection_id, product_id, position) SELECT 1 + n % 20, n + 1, n % 500 FROM bench_seq WHERE {$where} AND n % 200 < 20");
            DB::statement("INSERT INTO product_attribute_values (product_id, attribute_id, attribute_value_id)
                SELECT n + 1, {$material}, {$firstMaterial} + n % 12 FROM bench_seq WHERE {$where}
                UNION ALL SELECT n + 1, {$shape}, {$firstShape} + n % 6 FROM bench_seq WHERE {$where}");
        }
    }

    private function variants(int $products, int $colorType, int $sizeType, string $now): void
    {
        $base = DB::table('price_lists')->where('code', 'base')->value('id')
            ?? DB::table('price_lists')->insertGetId(['code' => 'base', 'name' => 'Giá niêm yết', 'currency_code' => 'VND', 'type' => 'base', 'priority' => 0, 'status' => 'active', 'lock_version' => 0, 'created_at' => $now, 'updated_at' => $now]);
        $location = (int) DB::table('locations')->orderBy('id')->value('id');

        foreach ($this->ranges($products) as [$from, $to]) {
            $where = "s.n >= {$from} AND s.n < {$to}";
            // Mỗi sản phẩm: 2 màu (n, n + 7), 5 size liên tiếp bắt đầu ở n % 4.
            DB::statement("INSERT INTO product_options (product_id, option_type_id, position, created_at, updated_at)
                SELECT n + 1, {$colorType}, 0, '{$now}', '{$now}' FROM bench_seq s WHERE {$where}
                UNION ALL SELECT n + 1, {$sizeType}, 1, '{$now}', '{$now}' FROM bench_seq s WHERE {$where}");
            // Trục màu = product_options có position 0, size = 1 (vừa chèn ở trên).
            DB::statement("INSERT INTO product_option_values (product_option_id, option_value_id, position, created_at, updated_at)
                SELECT po.id, c.id, k.n, '{$now}', '{$now}' FROM bench_seq s JOIN product_options po ON po.product_id = s.n + 1 AND po.position = 0
                    JOIN bench_seq k ON k.n < 2 JOIN bench_color c ON c.idx = (s.n + k.n * 7) % 24 WHERE {$where}
                UNION ALL SELECT po.id, z.id, k.n, '{$now}', '{$now}' FROM bench_seq s JOIN product_options po ON po.product_id = s.n + 1 AND po.position = 1
                    JOIN bench_seq k ON k.n < 5 JOIN bench_size z ON z.idx = s.n % 4 + k.n WHERE {$where}");

            $variant = "FROM bench_seq s JOIN bench_seq k ON k.n < 10
                JOIN bench_color c ON c.idx = (s.n + (k.n DIV 5) * 7) % 24 JOIN bench_size z ON z.idx = s.n % 4 + k.n % 5 WHERE {$where}";
            DB::statement("INSERT INTO variants (id, product_id, option_key, sku, status, weight_gram, lock_version, created_at, updated_at)
                SELECT s.n * 10 + k.n + 1, s.n + 1, CONCAT(c.id, '-', z.id), CONCAT('BP', LPAD(s.n + 1, 6, '0'), '-', LPAD(c.idx, 2, '0'), '-', z.code),
                    IF((s.n * 10 + k.n) % 37 = 0, 'inactive', 'active'), 300, 0, '{$now}', '{$now}' {$variant}");
            DB::statement("INSERT INTO variant_option_values (variant_id, option_type_id, option_value_id)
                SELECT s.n * 10 + k.n + 1, {$colorType}, c.id {$variant}
                UNION ALL SELECT s.n * 10 + k.n + 1, {$sizeType}, z.id {$variant}");
            // Giá niêm yết = list_price_min + bậc theo size; 20% sản phẩm có giá gạch.
            DB::statement("INSERT INTO prices (price_list_id, variant_id, amount, compare_at_amount, min_qty, created_at, updated_at)
                SELECT {$base}, s.n * 10 + k.n + 1, 149000 + (s.n % 40) * 10000 + (k.n % 5) * 10000,
                    IF(s.n % 5 = 0, (149000 + (s.n % 40) * 10000 + (k.n % 5) * 10000) * 5 DIV 4, NULL), 1, '{$now}', '{$now}'
                FROM bench_seq s JOIN bench_seq k ON k.n < 10 WHERE {$where}");
            // 10% SKU hết hàng.
            DB::statement("INSERT INTO stock_levels (location_id, variant_id, on_hand, reserved, safety_stock, created_at, updated_at)
                SELECT {$location}, s.n * 10 + k.n + 1, IF((s.n * 10 + k.n) % 10 = 0, 0, 5 + (s.n + k.n) % 40), 0, 0, '{$now}', '{$now}'
                FROM bench_seq s JOIN bench_seq k ON k.n < 10 WHERE {$where}");
        }
    }

    /**
     * Dựng `product_listing` / `product_facet_values` bằng ProductListingProjector (như ứng dụng), rồi ghi phần do Pricing và
     * Inventory sở hữu bằng SQL: giá niêm yết thấp nhất (cùng công thức với `prices`) và còn hàng.
     */
    private function listing(): void
    {
        Artisan::call('vani:catalog:rebuild-listing');
        DB::statement('UPDATE product_listing SET list_price_min = 149000 + ((product_id - 1) % 40) * 10000');
        DB::statement("UPDATE product_listing pl SET in_stock = EXISTS (SELECT 1 FROM variants v JOIN stock_levels sl ON sl.variant_id = v.id
            WHERE v.product_id = pl.product_id AND v.status = 'active' AND sl.on_hand > sl.reserved + sl.safety_stock)");
        DB::table('jobs')->where('queue', 'catalog-sync')->delete(); // Inventory tính lại in_stock theo event — đã ghi ở trên
    }

    private function customers(int $count, string $now): void
    {
        foreach ($this->ranges($count) as [$from, $to]) {
            DB::statement("INSERT INTO customers (id, public_id, phone, email, full_name, status, registered_at, created_at, updated_at)
                SELECT n + 1, CONCAT('01BENCHC', LPAD(n + 1, 18, '0')), CONCAT('+849', LPAD(n + 1, 8, '0')),
                    CONCAT('kh', n + 1, '@bench.test'), CONCAT('Khách ', n + 1), 'active',
                    TIMESTAMPADD(SECOND, n * 120, '2024-11-26 00:00:00'), '{$now}', '{$now}'
                FROM bench_seq WHERE n >= {$from} AND n < {$to}");
        }
    }

    private function orders(int $count, int $products, int $customers, int $offset = 0): void
    {
        $variants = $products * 10;
        while ($offset < $count) {
            $batch = min(1_000_000, $count - $offset);
            foreach ($this->ranges($batch) as [$from, $to]) {
                $id = "({$offset} + n + 1)";
                // ~2 năm đơn (12,6 giây/đơn ở 5 triệu đơn); 20% khách vãng lai. Trạng thái theo tỉ lệ của shop đang chạy; `stage` là cột sinh.
                $bucket = "({$id} % 100)";
                $status = "CASE WHEN {$bucket} < 80 THEN 'completed' WHEN {$bucket} < 85 THEN 'cancelled' WHEN {$bucket} < 88 THEN 'pending' ELSE 'processing' END";
                $payment = "CASE WHEN {$bucket} < 80 THEN 'cod_collected' WHEN {$bucket} < 85 THEN 'voided' WHEN {$bucket} < 97 THEN 'cod_pending' ELSE 'cod_collected' END";
                $fulfillment = "CASE WHEN {$bucket} < 80 THEN 'delivered' WHEN {$bucket} < 88 THEN 'unfulfilled' WHEN {$bucket} < 93 THEN 'allocated' WHEN {$bucket} < 97 THEN 'shipped' ELSE 'delivered' END";
                $placed = "TIMESTAMPADD(SECOND, FLOOR(({$id} - 1) * 63072000 / {$count}), '2024-11-26 00:00:00')";
                $customer = "IF({$id} % 5 = 0, NULL, 1 + ({$id} * 7919) % {$customers})";
                $phone = "CONCAT('+849', LPAD(IFNULL({$customer}, 90000000 + {$id} % 9000000), 8, '0'))";
                $subtotal = "(149000 + ({$id} % 40) * 10000) * 2";
                DB::statement("INSERT INTO orders (id, public_id, number, source, locale, customer_id, currency_code, order_status, payment_status, fulfillment_status, return_status, payment_method,
                        subtotal_amount, discount_amount, shipping_amount, tax_amount, total_amount, customer_snapshot, customer_phone, shipping_address, shipping_method, reservation_key, lock_version, placed_at, created_at, updated_at)
                    SELECT {$id}, CONCAT('01BENCHO', LPAD({$id}, 18, '0')), CONCAT('BN', LPAD({$id}, 9, '0')), 'web', 'vi', {$customer}, 'VND', {$status}, {$payment}, {$fulfillment}, 'none', 'cod',
                        {$subtotal}, 0, 30000, 0, {$subtotal} + 30000,
                        JSON_OBJECT('full_name', CONCAT('Khách ', {$id}), 'phone', {$phone}, 'email', NULL), {$phone},
                        JSON_OBJECT('province_code', '29', 'province_name', 'Thành phố Hồ Chí Minh', 'ward_code', '70101065', 'ward_name', 'Phường Bến Thành', 'street_line', '12 Lê Lợi'),
                        JSON_OBJECT('code', 'standard', 'label', 'Giao hàng tiêu chuẩn', 'fee', 30000, 'source', 'vani.shipping-flat-rate'),
                        CONCAT('order:01BENCHO', LPAD({$id}, 18, '0')), 0, {$placed}, {$placed}, TIMESTAMPADD(HOUR, 30, {$placed})
                    FROM bench_seq WHERE n >= {$from} AND n < {$to}");

                $id = "({$offset} + s.n + 1)";
                $variant = "(1 + ((({$id}) * 7 + k.n * 13) * 2654435761) % {$variants})";
                $product = "((({$variant}) - 1) DIV 10 + 1)";
                DB::statement("INSERT INTO order_lines (order_id, variant_id, product_id, sku, product_name, brand_id, brand_name, variant_options, quantity, cancelled_quantity, unit_amount, compare_at_amount, price_list_code,
                        subtotal_amount, discount_amount, total_amount, tax_rate_bp, tax_amount, meta, created_at, updated_at)
                    SELECT {$id}, {$variant}, {$product}, CONCAT('BP', LPAD({$product}, 6, '0'), '-', ({$variant} - 1) % 10), CONCAT('Sản phẩm ', {$product}),
                        1 + ({$product} - 1) % 50, CONCAT('Thương hiệu ', 1 + ({$product} - 1) % 50),
                        JSON_ARRAY(JSON_OBJECT('type', 'color', 'name', 'Màu', 'code', 'DEN', 'value', 'Đen'), JSON_OBJECT('type', 'size', 'name', 'Size', 'code', 'M', 'value', 'M')),
                        1, 0, 149000 + ({$id} % 40) * 10000, NULL, 'base', 149000 + ({$id} % 40) * 10000, 0, 149000 + ({$id} % 40) * 10000, 1000, 0,
                        NULL, TIMESTAMPADD(SECOND, FLOOR(({$id} - 1) * 63072000 / {$count}), '2024-11-26 00:00:00'), NULL
                    FROM bench_seq s CROSS JOIN bench_seq k WHERE k.n < 2 AND s.n >= {$from} AND s.n < {$to}");
            }
            $offset += $batch;
            $this->output->write("{$offset} ");
        }
    }

    /**
     * @return list<array{0: int, 1: int}> [từ, đến) theo lô CHUNK
     */
    private function ranges(int $count): array
    {
        $ranges = [];
        for ($from = 0; $from < $count; $from += self::CHUNK) {
            $ranges[] = [$from, min($count, $from + self::CHUNK)];
        }

        return $ranges;
    }
}
