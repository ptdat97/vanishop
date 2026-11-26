<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Closure;
use DateTimeImmutable;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Integration\Application\Api\OrderFeed;
use Modules\Ordering\Contracts\OrderStatistics;
use Symfony\Component\HttpFoundation\Response;

/**
 * Đo truy vấn nóng trên dữ liệu của `vani:bench:seed` ([db-refactor §2.2](docs/20-roadmap/db-refactor.md)): request đi qua
 * HTTP kernel trong cùng process (middleware, controller, truy vấn thật), mỗi kịch bản chạy 1 lần làm nóng + 5 lần đo, lấy
 * trung vị. Ghi bảng kết quả và EXPLAIN ANALYZE của truy vấn chậm nhất mỗi kịch bản vào `storage/logs/bench-scale.md`.
 *
 *   DB_DATABASE=vanishop_bench php artisan vani:bench:measure [--only=chuỗi trong tên kịch bản]
 */
final class BenchMeasureCommand extends Command
{
    protected $signature = 'vani:bench:measure {--only= : Chỉ chạy kịch bản có tên chứa một trong các chuỗi (phân cách bằng |)} {--runs=5} {--output=storage/logs/bench-scale.md}';

    protected $description = 'Đo truy vấn nóng trên DB đo hiệu năng (*_bench, MySQL)';

    /** @var array{queries: int, sql_ms: float, slowest: array{ms: float, sql: string, bindings: array<int, mixed>}|null} */
    private array $counter = ['queries' => 0, 'sql_ms' => 0.0, 'slowest' => null];

    public function handle(Kernel $kernel): int
    {
        $database = (string) DB::connection()->getDatabaseName();
        if (DB::getDriverName() !== 'mysql' || ! str_ends_with($database, '_bench')) {
            $this->error('Chỉ chạy trên MySQL với DB tên *_bench (đang: '.DB::getDriverName()." / {$database}).");

            return self::FAILURE;
        }
        set_time_limit(0);
        // Như production: job chạy nền không cộng vào request; nhiều đơn từ một IP không bị chặn rate limit.
        config(['queue.default' => 'database', 'vanishop.load_test.bypass_ips' => ['127.0.0.1'], 'app.debug' => false]);
        DB::listen(function (QueryExecuted $query): void {
            $this->counter['queries']++;
            $this->counter['sql_ms'] += $query->time;
            if ($this->counter['slowest'] === null || $query->time > $this->counter['slowest']['ms']) {
                $this->counter['slowest'] = ['ms' => $query->time, 'sql' => $query->sql, 'bindings' => $query->bindings];
            }
        });

        $rows = [];
        foreach ($this->scenarios($kernel) as [$label, $run]) {
            $only = array_filter(explode('|', mb_strtolower((string) $this->option('only'))));
            if ($only !== [] && ! array_filter($only, fn (string $part): bool => str_contains(mb_strtolower($label), $part))) {
                continue;
            }
            $result = $this->measure($run, max(1, (int) $this->option('runs')));
            $rows[] = [$label, ...$result];
            $this->line(sprintf('%-48s %8.1f ms  SQL %8.1f ms  %4d truy vấn  chậm nhất %7.1f ms', $label, $result['ms'], $result['sql_ms'], $result['queries'], $result['slowest']['ms'] ?? 0));
        }

        $this->report($rows);

        return self::SUCCESS;
    }

    /**
     * @return list<array{0: string, 1: Closure(int): mixed}>
     */
    private function scenarios(Kernel $kernel): array
    {
        $api = '/api/storefront/v1';
        $get = fn (string $uri): Closure => fn (): Response => $this->request($kernel, 'GET', $uri);
        $products = (int) DB::table('products')->max('id');
        $middle = (string) DB::table('products')->where('id', intdiv($products, 2))->value('slug');
        $variants = DB::table('variants')->join('stock_levels', 'stock_levels.variant_id', '=', 'variants.id')
            ->where('variants.status', 'active')->where('stock_levels.on_hand', '>', 20)->orderBy('variants.id')->limit(200)->pluck('variants.id')->all();
        $phone = (string) DB::table('orders')->whereNotNull('customer_id')->orderBy('id', 'desc')->value('customer_phone');
        $now = new DateTimeImmutable((string) DB::table('orders')->max('placed_at'));

        $cart = function (int $i) use ($kernel, $api, $variants): array {
            $created = json_decode((string) $this->request($kernel, 'POST', "{$api}/carts", [])->getContent(), true);
            $headers = ['X-Vani-Cart-Token' => (string) ($created['meta']['token'] ?? '')];
            $id = (string) ($created['data']['id'] ?? '');
            $this->request($kernel, 'POST', "{$api}/carts/{$id}/lines", ['variant_id' => $variants[$i % count($variants)], 'quantity' => 1], $headers);

            return [$id, $headers];
        };
        $payload = fn (int $i, int $total): array => [
            'contact' => ['full_name' => 'Khách đo', 'phone' => sprintf('09%08d', 30_000_000 + $i), 'email' => "bench{$i}@bench.test"],
            'shipping_address' => ['province_code' => '29', 'province_name' => 'Thành phố Hồ Chí Minh', 'ward_code' => '70101065', 'ward_name' => 'Phường Bến Thành', 'street_line' => '12 Lê Lợi'],
            'shipping_method' => 'standard', 'payment_method' => 'cod', 'expected_total' => $total,
        ];

        $staff = $this->staff();
        $admin = function (string $uri) use ($kernel, $staff): Closure {
            return function () use ($kernel, $staff, $uri): Response {
                Auth::guard('staff')->setUser($staff);

                return $this->request($kernel, 'GET', '/admin'.$uri);
            };
        };

        return [
            ['Listing: danh mục cha (gồm con), mới nhất', $get("{$api}/products?category=nhom-1")],
            ['Listing: danh mục cha + lọc màu', $get("{$api}/products?category=nhom-1&opt[color]=black")],
            ['Listing: danh mục cha + lọc màu + size', $get("{$api}/products?category=nhom-1&opt[color]=black&opt[size]=M")],
            ['Listing: toàn bộ, giá tăng dần', $get("{$api}/products?sort=price_asc")],
            ['Listing: danh mục con, giá giảm dần', $get("{$api}/products?category=loai-25&sort=price_desc")],
            ['Listing: thuộc tính + thương hiệu', $get("{$api}/products?attr[chat-lieu]=chat-lieu-3&brand=thuong-hieu-7")],
            ['Listing: danh mục cha, trang 40', $get("{$api}/products?category=nhom-1&page=40")],
            ['Tìm chữ: "ao so mi lua"', $get("{$api}/products?q=".rawurlencode('áo sơ mi lụa'))],
            ['Tìm chữ: mã một phần "BP0500"', $get("{$api}/products?q=BP0500")],
            ['PDP: API chi tiết sản phẩm', $get("{$api}/products/{$middle}")],
            ['Giỏ: tạo giỏ + thêm 1 dòng', fn (int $i) => $cart($i)],
            ['Checkout: đặt đơn COD (giữ tồn)', function (int $i) use ($kernel, $api, $cart, $payload): Response {
                [$id, $headers] = $cart(1000 + $i);
                $quote = json_decode((string) $this->request($kernel, 'POST', "{$api}/checkout/{$id}/quote", $payload($i, 0), $headers)->getContent(), true);

                return $this->request($kernel, 'POST', "{$api}/checkout/{$id}/orders", $payload($i, (int) ($quote['data']['total']['amount'] ?? 0)), [...$headers, 'Idempotency-Key' => 'bench-'.Str::uuid()]);
            }],
            ['Admin: danh sách đơn', $admin('/orders/orders')],
            ['Admin: đơn theo bước "chờ xác nhận"', $admin('/orders/orders?stage=to_confirm')],
            ['Admin: đơn theo trạng thái "đã huỷ"', $admin('/orders/orders?status=cancelled')],
            ['Admin: tìm đơn theo SĐT khách', $admin('/orders/orders?q='.rawurlencode($phone))],
            ['Admin: danh sách sản phẩm', $admin('/catalog/products')],
            ['Admin: lưới tồn', $admin('/inventory/stock')],
            ['Admin: tổng quan', $admin('')],
            ['Sync API: đơn đổi trong 1 ngày, 50 đơn', fn () => app(OrderFeed::class)->page($now->modify('-1 day')->format(DATE_ATOM), null, 50)],
            ['Báo cáo: sản phẩm bán chạy 30 ngày', fn () => app(OrderStatistics::class)->topProducts($now->modify('-30 days'), $now, 20)],
        ];
    }

    /**
     * @param  Closure(int): mixed  $run
     * @return array{ms: float, sql_ms: float, queries: int, status: int|null, slowest: array{ms: float, sql: string, bindings: array<int, mixed>}|null}
     */
    private function measure(Closure $run, int $runs): array
    {
        $run(0);
        $samples = [];
        for ($i = 1; $i <= $runs; $i++) {
            $this->counter = ['queries' => 0, 'sql_ms' => 0.0, 'slowest' => null];
            $started = hrtime(true);
            $result = $run($i);
            $samples[] = ['ms' => (hrtime(true) - $started) / 1e6, 'sql_ms' => $this->counter['sql_ms'], 'queries' => $this->counter['queries'],
                'status' => $result instanceof Response ? $result->getStatusCode() : null, 'slowest' => $this->counter['slowest']];
        }
        usort($samples, fn (array $a, array $b): int => $a['ms'] <=> $b['ms']);

        return $samples[intdiv(count($samples), 2)];
    }

    /**
     * @param  array<string, mixed>  $body
     * @param  array<string, string>  $headers
     */
    private function request(Kernel $kernel, string $method, string $uri, array $body = [], array $headers = []): Response
    {
        $server = ['HTTP_ACCEPT' => 'application/json', 'REMOTE_ADDR' => '127.0.0.1'];
        foreach ($headers as $name => $value) {
            $server['HTTP_'.strtoupper(str_replace('-', '_', $name))] = $value;
        }
        if ($method !== 'GET') {
            $server['CONTENT_TYPE'] = 'application/json';
        }
        $request = Request::create($uri, $method, [], [], [], $server, $method === 'GET' ? null : (string) json_encode($body));
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);

        return $response;
    }

    private function staff(): StaffUser
    {
        $email = 'bench-owner@bench.test';
        if (! StaffUser::query()->where('email', $email)->exists()) {
            Artisan::call('vani:staff:create-owner', ['email' => $email, 'name' => 'Bench', '--password' => Str::random(24)]);
        }

        return StaffUser::query()->where('email', $email)->firstOrFail();
    }

    /**
     * @param  list<array{0: string, ms: float, sql_ms: float, queries: int, status: int|null, slowest: array{ms: float, sql: string, bindings: array<int, mixed>}|null}>  $rows
     */
    private function report(array $rows): void
    {
        $counts = fn (string $table): string => number_format((int) DB::table($table)->count(), 0, ',', '.');
        $lines = ['# Đo truy vấn nóng — '.now()->format('Y-m-d H:i'), '',
            sprintf('Dữ liệu: %s sản phẩm, %s SKU, %s đơn, %s dòng đơn. MySQL %s, buffer pool %d MB.', $counts('products'), $counts('variants'), $counts('orders'), $counts('order_lines'),
                (string) DB::selectOne('select version() as v')->v, (int) DB::selectOne('select @@innodb_buffer_pool_size as b')->b / 1048576), '',
            '| Kịch bản | HTTP | ms (trung vị) | SQL ms | Truy vấn | Truy vấn chậm nhất ms |', '|---|---:|---:|---:|---:|---:|'];
        foreach ($rows as $row) {
            $lines[] = sprintf('| %s | %s | %.0f | %.0f | %d | %.0f |', $row[0], $row['status'] ?? '—', $row['ms'], $row['sql_ms'], $row['queries'], $row['slowest']['ms'] ?? 0);
        }
        $lines[] = '';
        foreach ($rows as $row) {
            $slowest = $row['slowest'];
            if ($slowest === null || ! preg_match('/^\s*select/i', $slowest['sql'])) {
                continue;
            }
            $plan = DB::select('EXPLAIN ANALYZE '.$slowest['sql'], $slowest['bindings']);
            $lines[] = "## {$row[0]}";
            $lines[] = '';
            $lines[] = '```sql';
            $lines[] = mb_strimwidth((string) preg_replace('/\s+/', ' ', $slowest['sql']), 0, 1500, '…');
            $lines[] = '```';
            $lines[] = '';
            $lines[] = '```text';
            $lines[] = (string) (array_values((array) ($plan[0] ?? []))[0] ?? '');
            $lines[] = '```';
            $lines[] = '';
        }
        $path = base_path((string) $this->option('output'));
        file_put_contents($path, implode("\n", $lines)."\n");
        $this->info("Đã ghi {$path}.");
    }
}
