<?php

/*
| R37 / ADR-044 — Storefront API-first: Storefront API v1 là hợp đồng, Blade là một cách dùng.
|
| 1. Mọi thao tác ghi của storefront Blade có endpoint `/api/storefront/v1` tương ứng (bảng STOREFRONT_API_PAIRS). Route ghi
|    mới của Blade thiếu cặp → đỏ: thêm endpoint API trước, rồi khai báo cặp ở đây.
| 2. Các cặp đã gom hợp đồng đầu vào dùng CÙNG một FormRequest ở cả hai phía (luật validate viết một lần).
| 3. JS/view của theme và plugin lấy dữ liệu từ Storefront API, không từ endpoint JSON riêng (trừ `/_vani/phien`).
| 4. Plugin có trang storefront phải có Storefront API cho cùng dữ liệu (trừ PLUGIN_PAGES_WITHOUT_API).
| 5. API của plugin được mô tả trong openapi.json của plugin và test của plugin đối chiếu với nó.
*/

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Route;
use Modules\Shared\Support\StoreLocale;

/**
 * Route ghi của Blade → endpoint Storefront API làm cùng việc.
 *
 * @var array<string, list<string>>
 */
const STOREFRONT_API_PAIRS = [
    'storefront.cart.add' => ['api.storefront.v1.carts.lines.store'],
    'storefront.cart.update' => ['api.storefront.v1.carts.lines.update'],
    'storefront.cart.remove' => ['api.storefront.v1.carts.lines.destroy'],
    'storefront.cart.select' => ['api.storefront.v1.carts.selection'],
    'storefront.cart.voucher' => ['api.storefront.v1.carts.vouchers'],
    'storefront.checkout.store' => ['api.storefront.v1.checkout.quote', 'api.storefront.v1.checkout.orders.store'],
    'storefront.order.received' => ['api.storefront.v1.orders.received'],
    'storefront.order.pay' => ['api.storefront.v1.orders.payment.retry'],
    'storefront.order.returns.store' => ['api.storefront.v1.orders.returns.store'],
    'storefront.order.returns.shipped' => ['api.storefront.v1.orders.returns.shipped'],
    'storefront.order.returns.cancel' => ['api.storefront.v1.orders.returns.cancel'],
    'storefront.account.otp' => ['api.storefront.v1.auth.otp.request'],
    'storefront.account.verify' => ['api.storefront.v1.auth.otp.verify'],
    'storefront.account.logout' => ['api.storefront.v1.auth.logout'],
    'storefront.account.order.cancel' => ['api.storefront.v1.me.orders.cancel'],
    'storefront.account.profile.update' => ['api.storefront.v1.me.update'],
    'storefront.account.password.update' => ['api.storefront.v1.me.password'],
    'storefront.account.addresses.store' => ['api.storefront.v1.me.addresses.store'],
    'storefront.account.addresses.update' => ['api.storefront.v1.me.addresses.update'],
    'storefront.account.addresses.default' => ['api.storefront.v1.me.addresses.update'],
    'storefront.account.addresses.destroy' => ['api.storefront.v1.me.addresses.destroy'],
    'storefront.account.delete.otp' => ['api.storefront.v1.auth.otp.request'],
    'storefront.account.delete.confirm' => ['api.storefront.v1.me.destroy'],
    'storefront.track.search' => ['api.storefront.v1.orders.track'],
    // Plugin (route chỉ có khi plugin bật): nút tim là form thường khi tắt JS; có JS thì wishlist.js gọi API.
    'storefront.p.vani-wishlist.toggle' => ['api.storefront.x.vani-wishlist.items.store', 'api.storefront.x.vani-wishlist.items.destroy'],
];

/**
 * Route ghi của Blade chưa có endpoint API — mỗi mục có lý do và đợt xử lý; danh sách chỉ được ngắn lại.
 *
 * @var array<string, string>
 */
const STOREFRONT_API_PENDING = [
];

/**
 * Các cặp phải dùng chung một FormRequest (đã gom ở đợt A1).
 *
 * @var list<string>
 */
const STOREFRONT_SHARED_INPUT = [
    'storefront.cart.add', 'storefront.cart.update', 'storefront.cart.select', 'storefront.cart.voucher',
    'storefront.checkout.store', 'storefront.order.returns.store',
    'storefront.account.profile.update', 'storefront.account.password.update',
    'storefront.account.addresses.store', 'storefront.account.addresses.update',
];

/**
 * @return array<string, Route> route ghi của storefront Blade (ngôn ngữ mặc định), theo tên
 */
function bladeWriteRoutes(): array
{
    $routes = [];
    foreach (app('router')->getRoutes()->getRoutes() as $route) {
        $name = (string) $route->getName();
        $locale = '/^storefront\.('.implode('|', array_map('preg_quote', StoreLocale::supported())).')\./';
        if (str_starts_with($name, 'storefront.') && preg_match($locale, $name) !== 1 && array_intersect($route->methods(), ['POST', 'PUT', 'PATCH', 'DELETE']) !== []) {
            $routes[$name] = $route;
        }
    }

    return $routes;
}

/** FormRequest mà action của route nhận (null khi không có). */
function routeFormRequest(Route $route): ?string
{
    $action = $route->getAction('uses');
    if (! is_string($action)) {
        return null;
    }
    [$class, $method] = str_contains($action, '@') ? explode('@', $action) : [$action, '__invoke'];
    foreach ((new ReflectionMethod($class, $method))->getParameters() as $parameter) {
        $type = $parameter->getType();
        if ($type instanceof ReflectionNamedType && ! $type->isBuiltin() && is_subclass_of($type->getName(), FormRequest::class)) {
            return $type->getName();
        }
    }

    return null;
}

it('R37: mọi thao tác ghi của storefront Blade có endpoint Storefront API tương ứng', function () {
    $missing = array_values(array_diff(array_keys(bladeWriteRoutes()), array_keys(STOREFRONT_API_PAIRS), array_keys(STOREFRONT_API_PENDING)));

    expect($missing)->toBe([], 'Thêm endpoint /api/storefront/v1 cho route này rồi khai báo trong STOREFRONT_API_PAIRS (ADR-044)');
});

it('R37: bảng cặp Blade ↔ API trỏ tới route có thật; danh sách chờ không giữ route đã có cặp', function () {
    $routes = app('router')->getRoutes();
    $broken = [];
    foreach (STOREFRONT_API_PAIRS as $blade => $apis) {
        // Route của plugin chỉ đăng ký khi plugin bật.
        if (str_starts_with($blade, 'storefront.p.') && $routes->getByName($blade) === null) {
            continue;
        }
        if ($routes->getByName($blade) === null) {
            $broken[] = "{$blade} (Blade)";
        }
        foreach ($apis as $api) {
            if ($routes->getByName($api) === null) {
                $broken[] = "{$api} (API)";
            }
        }
    }
    // Route của plugin chỉ có khi plugin bật, nên chỉ kiểm: mục chờ đã có cặp thì phải rời danh sách chờ.
    $stale = array_values(array_filter(array_keys(STOREFRONT_API_PENDING), fn (string $name): bool => isset(STOREFRONT_API_PAIRS[$name])));

    expect($broken)->toBe([])->and($stale)->toBe([]);
});

it('R37: cặp đã gom dùng CÙNG một FormRequest ở Blade và API (luật đầu vào viết một lần)', function () {
    $routes = app('router')->getRoutes();
    $diverged = [];
    foreach (STOREFRONT_SHARED_INPUT as $blade) {
        $expected = routeFormRequest($routes->getByName($blade));
        foreach (STOREFRONT_API_PAIRS[$blade] as $api) {
            $actual = routeFormRequest($routes->getByName($api));
            if ($expected === null || $actual !== $expected) {
                $diverged[] = "{$blade}: ".($expected ?? 'không có FormRequest')." ≠ {$api}: ".($actual ?? 'không có FormRequest');
            }
        }
    }

    expect($diverged)->toBe([]);
});

it('R37: JS và view của theme/plugin không gọi endpoint JSON riêng ngoài Storefront API', function () {
    // Ngoại lệ duy nhất: /_vani/phien — "đường ống" của Blade khi trang được cache CDN (CSRF của form, flash, lỗi form lần
    // gửi trước); không phải dữ liệu cửa hàng (ADR-044, api-first-refactor A3).
    $allowed = ['/_vani/phien', "'storefront.session'"];
    $pattern = '#/_vani/[a-z]+|storefront\.session\.[a-z]+|p/vani-wishlist/(ids|merge)|(?<!x\.)vani-wishlist\.(ids|merge)\b#';
    $offenders = [];
    $roots = [...glob(base_path('custom/theme/*'), GLOB_ONLYDIR), ...glob(base_path('custom/plugin/*'), GLOB_ONLYDIR)];
    foreach ($roots as $root) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)) as $file) {
            $path = (string) $file;
            if (! preg_match('/\.(js|blade\.php)$/', $path) || str_contains($path, '/Tests/')) {
                continue;
            }
            foreach (file($path) ?: [] as $number => $line) {
                if (preg_match_all($pattern, $line, $matches) > 0 && array_diff($matches[0], $allowed) !== []) {
                    $offenders[] = substr($path, strlen(base_path()) + 1).':'.($number + 1);
                }
            }
        }
    }

    expect($offenders)->toBe([], 'Gọi Storefront API (phiên cùng nguồn, ADR-044) thay cho endpoint JSON riêng');
});

/**
 * Plugin có trang Blade nhưng KHÔNG cần Storefront API (A5, ADR-044): trang chỉ là điểm quay về/trình bày, không mang
 * dữ liệu cửa hàng mà client khác cần.
 *
 * @var array<string, string>
 */
const PLUGIN_PAGES_WITHOUT_API = [
    'vani.vnpay' => 'trang khách quay về từ cổng thanh toán: xác minh chữ ký rồi chuyển tới trang đơn; app dùng GET /payments/{payment}',
];

/**
 * @return array<string, string> plugin id → mã nguồn ServiceProvider
 */
function pluginProviders(): array
{
    $providers = [];
    foreach (glob(base_path('custom/plugin/*/vanishop.json')) as $manifest) {
        $id = json_decode((string) file_get_contents($manifest), true, flags: JSON_THROW_ON_ERROR)['id'];
        $providers[$id] = implode("\n", array_map('file_get_contents', glob(dirname($manifest).'/*ServiceProvider.php')));
    }

    return $providers;
}

it('R37: plugin có trang storefront (storefrontPages/accountRoutes) phải có Storefront API cho cùng dữ liệu (storefrontRoutes)', function () {
    $missing = [];
    $stale = [];
    foreach (pluginProviders() as $id => $source) {
        $hasPages = preg_match('/\$this->(storefrontPages|accountRoutes)\(/', $source) === 1;
        $hasApi = preg_match('/\$this->storefrontRoutes\(/', $source) === 1;
        if ($hasPages && ! $hasApi && ! array_key_exists($id, PLUGIN_PAGES_WITHOUT_API)) {
            $missing[] = $id;
        }
        if (array_key_exists($id, PLUGIN_PAGES_WITHOUT_API) && (! $hasPages || $hasApi)) {
            $stale[] = $id;
        }
    }

    expect($missing)->toBe([], 'Thêm storefrontRoutes() cho dữ liệu của trang (ADR-044), hoặc ghi lý do vào PLUGIN_PAGES_WITHOUT_API')
        ->and($stale)->toBe([], 'Bỏ plugin này khỏi PLUGIN_PAGES_WITHOUT_API');
});

it('R37: plugin có storefrontRoutes() mô tả API trong openapi.json và test của plugin đối chiếu route + response với nó', function () {
    $missing = [];
    foreach (glob(base_path('custom/plugin/*/vanishop.json')) as $manifest) {
        $dir = dirname($manifest);
        $source = implode("\n", array_map('file_get_contents', glob("{$dir}/*ServiceProvider.php")));
        if (preg_match('/\$this->storefrontRoutes\(/', $source) !== 1) {
            continue;
        }
        $tests = implode("\n", array_map('file_get_contents', glob("{$dir}/Tests/Feature/*.php") ?: []));
        foreach ([
            'openapi.json' => is_file("{$dir}/openapi.json"),
            'assertRoutesDocumented' => str_contains($tests, 'assertRoutesDocumented('),
            'assertResponse' => str_contains($tests, 'assertResponse('),
        ] as $requirement => $met) {
            if (! $met) {
                $missing[] = basename($dir).": {$requirement}";
            }
        }
    }

    expect($missing)->toBe([], 'Xem plugin-system §10b: openapi.json + Modules\Extension\Testing\StorefrontApiContract trong test của plugin');
});
