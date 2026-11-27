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
    'storefront.order.returns.photos' => ['api.storefront.v1.orders.returns.photos'],
    'storefront.order.returns.cancel' => ['api.storefront.v1.orders.returns.cancel'],
    'storefront.account.authenticate' => ['api.storefront.v1.auth.login'],
    'storefront.account.register.store' => ['api.storefront.v1.auth.register'],
    'storefront.account.register.otp.verify' => ['api.storefront.v1.auth.register.verify-otp'],
    'storefront.account.password.email' => ['api.storefront.v1.auth.password.forgot'],
    'storefront.account.password.update-forgotten' => ['api.storefront.v1.auth.password.reset'],
    'storefront.account.logout' => ['api.storefront.v1.auth.logout'],
    'storefront.account.order.cancel' => ['api.storefront.v1.me.orders.cancel'],
    'storefront.account.profile.update' => ['api.storefront.v1.me.update'],
    'storefront.account.marketing.update' => ['api.storefront.v1.me.consents.update'],
    'storefront.account.unsubscribe.confirm' => ['api.storefront.v1.me.consents.update'],
    'storefront.account.email.resend' => ['api.storefront.v1.me.email.verification'],
    'storefront.account.password.update' => ['api.storefront.v1.me.password'],
    'storefront.account.addresses.store' => ['api.storefront.v1.me.addresses.store'],
    'storefront.account.addresses.update' => ['api.storefront.v1.me.addresses.update'],
    'storefront.account.addresses.default' => ['api.storefront.v1.me.addresses.update'],
    'storefront.account.addresses.destroy' => ['api.storefront.v1.me.addresses.destroy'],
    'storefront.account.delete.confirm' => ['api.storefront.v1.me.destroy'],
    'storefront.account.delete.code' => ['api.storefront.v1.me.deletion.code'],
    'storefront.track.search' => ['api.storefront.v1.orders.track'],
    // Plugin (route chỉ có khi plugin bật).
    'storefront.p.vani-phone-otp-login.request' => ['api.storefront.x.vani-phone-otp-login.otp.request'],
    'storefront.p.vani-phone-otp-login.verify' => ['api.storefront.x.vani-phone-otp-login.otp.verify'],
];

/**
 * Route ghi của Blade chưa có endpoint API — mỗi mục có lý do và đợt xử lý; danh sách chỉ được ngắn lại.
 *
 * @var array<string, string>
 */
const STOREFRONT_API_PENDING = [
];

/**
 * Các cặp phải dùng chung một FormRequest — luật đầu vào viết một lần (đợt A1; AF1 gom nốt các cặp còn validate tay).
 *
 * @var list<string>
 */
const STOREFRONT_SHARED_INPUT = [
    'storefront.cart.add', 'storefront.cart.update', 'storefront.cart.select', 'storefront.cart.voucher',
    'storefront.checkout.store', 'storefront.order.returns.store',
    'storefront.account.profile.update', 'storefront.account.password.update', 'storefront.account.marketing.update',
    'storefront.account.register.store', 'storefront.account.register.otp.verify', 'storefront.account.password.update-forgotten',
    'storefront.account.addresses.store', 'storefront.account.addresses.update',
    'storefront.order.received', 'storefront.order.returns.shipped', 'storefront.order.returns.photos',
    'storefront.account.authenticate', 'storefront.account.password.email', 'storefront.account.order.cancel',
    'storefront.account.delete.confirm', 'storefront.track.search', 'storefront.order.pay',
];

/**
 * Cặp không có đầu vào cần validate (định danh nằm trên URL/phiên) hoặc có lý do riêng — mỗi mục ghi lý do.
 *
 * @var array<string, string>
 */
const STOREFRONT_NO_INPUT = [
    'storefront.cart.remove' => 'dòng giỏ nằm trên URL',
    'storefront.order.returns.cancel' => 'yêu cầu đổi trả nằm trên URL',
    'storefront.account.logout' => 'chỉ cần phiên/token',
    'storefront.account.email.resend' => 'chỉ cần phiên/token',
    'storefront.account.delete.code' => 'chỉ cần phiên/token (gửi mã tới liên hệ trên hồ sơ)',
    'storefront.account.unsubscribe.confirm' => 'khách và kênh nằm trên URL có chữ ký (link trong email); API dùng `PUT /me/consents` (ConsentRequest)',
    'storefront.account.addresses.default' => 'Blade chỉ đặt mặc định (id trên URL); API đặt mặc định qua `PUT /me/addresses/{id}` (AddressRequest)',
    'storefront.account.addresses.destroy' => 'địa chỉ nằm trên URL',
    'storefront.p.vani-phone-otp-login.request' => 'plugin kết nối OTP đang đóng băng (chỉ sửa lỗi) — gom khi mở lại',
    'storefront.p.vani-phone-otp-login.verify' => 'plugin kết nối OTP đang đóng băng (chỉ sửa lỗi) — gom khi mở lại',
];

/** Thân method của action (để tìm validate viết tay). */
function routeActionSource(Route $route): ?string
{
    $action = $route->getAction('uses');
    if (! is_string($action)) {
        return null;
    }
    [$class, $method] = str_contains($action, '@') ? explode('@', $action) : [$action, '__invoke'];
    $reflection = new ReflectionMethod($class, $method);
    $lines = file((string) $reflection->getFileName());

    return implode('', array_slice($lines, $reflection->getStartLine() - 1, $reflection->getEndLine() - $reflection->getStartLine() + 1));
}

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
        if ($routes->getByName($blade) === null) {
            continue; // route của plugin chưa bật
        }
        $expected = routeFormRequest($routes->getByName($blade));
        foreach (STOREFRONT_API_PAIRS[$blade] as $api) {
            $route = $routes->getByName($api);
            // Endpoint DELETE mang định danh trên URL, không có thân request (vd. bỏ một mục yêu thích).
            if ($route === null || in_array('DELETE', $route->methods(), true)) {
                continue;
            }
            $actual = routeFormRequest($route);
            if ($expected === null || $actual !== $expected) {
                $diverged[] = "{$blade}: ".($expected ?? 'không có FormRequest')." ≠ {$api}: ".($actual ?? 'không có FormRequest');
            }
        }
    }

    expect($diverged)->toBe([]);
});

it('AF1: mọi cặp Blade ↔ API được xếp loại: dùng chung FormRequest hoặc không có đầu vào (có lý do)', function () {
    $classified = [...STOREFRONT_SHARED_INPUT, ...array_keys(STOREFRONT_NO_INPUT)];

    expect(array_values(array_diff(array_keys(STOREFRONT_API_PAIRS), $classified)))->toBe([], 'Cặp mới: dùng chung FormRequest (STOREFRONT_SHARED_INPUT) hoặc ghi lý do ở STOREFRONT_NO_INPUT')
        ->and(array_values(array_intersect(STOREFRONT_SHARED_INPUT, array_keys(STOREFRONT_NO_INPUT))))->toBe([])
        ->and(array_values(array_diff($classified, array_keys(STOREFRONT_API_PAIRS))))->toBe([]);
});

it('AF1: action của cặp Blade ↔ API không validate tay — luật đầu vào ở FormRequest dùng chung', function () {
    $routes = app('router')->getRoutes();
    $inline = [];
    foreach (STOREFRONT_API_PAIRS as $blade => $apis) {
        if (array_key_exists($blade, STOREFRONT_NO_INPUT) && str_contains(STOREFRONT_NO_INPUT[$blade], 'đóng băng')) {
            continue;
        }
        foreach ([$blade, ...$apis] as $name) {
            $route = $routes->getByName($name);
            $source = $route === null ? null : routeActionSource($route);
            if ($source !== null && preg_match('/->validate(WithBag)?\(/', $source) === 1) {
                $inline[] = "{$name} ({$route->getActionName()})";
            }
        }
    }

    expect(array_values(array_unique($inline)))->toBe([]);
});

/**
 * L45 — cùng tầng xử lý: lớp chỉ lo "vận chuyển" dữ liệu theo kênh (đọc/ghi phiên, định dạng response) — không tính khi so.
 *
 * @var list<string>
 */
const STOREFRONT_CHANNEL_ADAPTERS = [
    'Modules\Storefront\Application\NativeCart',
    'Modules\Storefront\Application\CartPresenter',
    'Modules\Storefront\Application\CheckoutPresenter',
    'Modules\Storefront\Application\PaymentPresenter',
    'Modules\Storefront\Application\OrderPresenter',
];

/**
 * Private method của controller Blade chỉ lo việc của kênh web — không đi vào khi dò service: nhận diện khách từ phiên
 * (phía API làm ở middleware `AuthenticateCustomer`), dựng phản hồi cho JS.
 *
 * @var array<string, string>
 */
const STOREFRONT_CHANNEL_HELPERS = [
    'Modules\Storefront\Http\Controllers\Web\AccountController::customer' => 'nhận diện khách từ phiên',
    'Modules\Storefront\Http\Controllers\Web\AccountController::required' => 'nhận diện khách từ phiên',
    'Modules\Storefront\Http\Controllers\Web\AccountSettingsController::required' => 'nhận diện khách từ phiên',
    'Modules\Storefront\Http\Controllers\Web\CartController::payload' => 'phản hồi JSON cho JS (mini-cart HTML, mã giảm giá đang giữ để hiển thị)',
];

/**
 * Cặp Blade ↔ API mà service tầng Application/Contracts hai phía dùng KHÁC nhau — mỗi mục ghi lý do. Danh sách chỉ được
 * ngắn lại; khác biệt mới mà không ghi lý do → đỏ.
 *
 * @var array<string, string>
 */
const STOREFRONT_SERVICE_DIFFERENCES = [
    'storefront.checkout.store' => 'form checkout Blade có ô mã giảm giá ghi vào giỏ (CartVoucher) — app gọi PUT /carts/{id}/vouchers riêng; đặt hàng/báo giá cùng Checkout',
    'storefront.account.authenticate' => 'gộp giỏ vãng lai vào khách: Blade theo giỏ trong phiên (CustomerSignIn → NativeCart), API theo cart_id + token (Carts::attachToCustomer); đăng nhập cùng CustomerSessions',
    'storefront.account.register.store' => 'tự chấp nhận đăng ký (0.5.114) đăng nhập ngay: gộp giỏ như đăng nhập — Blade CustomerSignIn, API Carts::attachToCustomer; đăng ký cùng CustomerSessions',
    'storefront.account.register.otp.verify' => 'như đăng nhập: gộp giỏ — Blade CustomerSignIn, API Carts::attachToCustomer; xác minh cùng CustomerSessions',
];

/**
 * Service tầng Application/Contracts mà action của route dùng: tham số của action + property constructor được dùng trong
 * thân action và trong private method nó gọi (đệ quy trong cùng class). Bỏ FormRequest/DTO và lớp vận chuyển theo kênh.
 *
 * @return list<string>
 */
function routeServices(Route $route): array
{
    $action = $route->getAction('uses');
    if (! is_string($action)) {
        return [];
    }
    [$class, $method] = str_contains($action, '@') ? explode('@', $action) : [$action, '__invoke'];
    $reflection = new ReflectionClass($class);
    $types = [];
    foreach ($reflection->getConstructor()?->getParameters() ?? [] as $parameter) {
        $type = $parameter->getType();
        if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
            $types['$this->'.$parameter->getName()] = $type->getName();
        }
    }
    $lines = file((string) $reflection->getFileName());
    $body = fn (string $name): string => (fn (ReflectionMethod $m): string => implode('', array_slice($lines, $m->getStartLine() - 1, $m->getEndLine() - $m->getStartLine() + 1)))(new ReflectionMethod($class, $name));

    $found = [];
    foreach ((new ReflectionMethod($class, $method))->getParameters() as $parameter) {
        $type = $parameter->getType();
        if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
            $found[] = $type->getName();
        }
    }
    $queue = [$method];
    $seen = [];
    while ($queue !== []) {
        $name = array_shift($queue);
        if (isset($seen[$name]) || ($name !== $method && array_key_exists("{$class}::{$name}", STOREFRONT_CHANNEL_HELPERS))) {
            continue;
        }
        $seen[$name] = true;
        $source = $body($name);
        foreach ($types as $property => $type) {
            if (preg_match('/'.preg_quote($property, '/').'\b/', $source) === 1) {
                $found[] = $type;
            }
        }
        preg_match_all('/\$this->([a-zA-Z_]+)\(/', $source, $calls);
        foreach ($calls[1] as $called) {
            if ($reflection->hasMethod($called) && $reflection->getMethod($called)->getDeclaringClass()->getName() === $class) {
                $queue[] = $called;
            }
        }
    }

    return array_values(array_unique(array_filter($found, fn (string $type): bool => preg_match('/^Modules\\\\[A-Za-z]+\\\\(Contracts|Application)\\\\/', $type) === 1
        && preg_match('/\\\\Contracts\\\\(Requests|Data)\\\\/', $type) !== 1
        && is_subclass_of($type, FormRequest::class) === false
        && ! in_array($type, STOREFRONT_CHANNEL_ADAPTERS, true))));
}

it('L45: mỗi cặp Blade ↔ API gọi cùng service tầng Application/Contracts (khác nhau phải ghi lý do)', function () {
    $routes = app('router')->getRoutes();
    $diverged = [];
    $explained = [];
    foreach (STOREFRONT_API_PAIRS as $blade => $apis) {
        $bladeRoute = $routes->getByName($blade);
        if ($bladeRoute === null) {
            continue; // route của plugin chưa bật
        }
        $bladeServices = routeServices($bladeRoute);
        $apiServices = array_values(array_unique(array_merge(...array_map(fn (string $api): array => ($route = $routes->getByName($api)) === null ? [] : routeServices($route), $apis))));
        $onlyBlade = array_values(array_diff($bladeServices, $apiServices));
        $onlyApi = array_values(array_diff($apiServices, $bladeServices));
        if ($onlyBlade === [] && $onlyApi === []) {
            continue;
        }
        if (array_key_exists($blade, STOREFRONT_SERVICE_DIFFERENCES)) {
            $explained[] = $blade;

            continue;
        }
        $short = fn (array $classes): string => implode(', ', array_map(fn (string $c): string => preg_replace('/^Modules\\\\/', '', $c), $classes)) ?: '—';
        $diverged[] = "{$blade}: chỉ Blade dùng [{$short($onlyBlade)}], chỉ API dùng [{$short($onlyApi)}]";
    }
    $stale = array_values(array_diff(array_keys(STOREFRONT_SERVICE_DIFFERENCES), $explained, array_filter(array_keys(STOREFRONT_SERVICE_DIFFERENCES), fn (string $name): bool => $routes->getByName($name) === null)));

    expect($diverged)->toBe([], 'Blade và API phải gọi cùng service (ADR-044) — sửa phía lệch, hoặc ghi lý do ở STOREFRONT_SERVICE_DIFFERENCES')
        ->and($stale)->toBe([], 'Cặp đã khớp: bỏ khỏi STOREFRONT_SERVICE_DIFFERENCES');
});

/**
 * AF2 — phía đọc: trang Blade GET → endpoint Storefront API GET cho cùng dữ liệu (app/headless dựng được cùng màn hình).
 *
 * @var array<string, list<string>>
 */
const STOREFRONT_API_READ_PAIRS = [
    'storefront.home' => ['api.storefront.v1.pages.home', 'api.storefront.v1.store.show', 'api.storefront.v1.menus.show'],
    'storefront.categories' => ['api.storefront.v1.categories.index'],
    'storefront.collection' => ['api.storefront.v1.collections.show', 'api.storefront.v1.products.index'],
    'storefront.category' => ['api.storefront.v1.categories.show', 'api.storefront.v1.products.index'],
    'storefront.brands' => ['api.storefront.v1.brands.index'],
    'storefront.brand' => ['api.storefront.v1.brands.show', 'api.storefront.v1.products.index'],
    'storefront.search' => ['api.storefront.v1.products.index', 'api.storefront.v1.search.suggestions'],
    'storefront.product' => ['api.storefront.v1.products.show'],
    'storefront.cart' => ['api.storefront.v1.carts.show', 'api.storefront.v1.me.cart'],
    'storefront.checkout' => ['api.storefront.v1.carts.show', 'api.storefront.v1.me.addresses.index', 'api.storefront.v1.address.provinces'],
    'storefront.order' => ['api.storefront.v1.orders.show', 'api.storefront.v1.payments.show'],
    'storefront.track' => ['api.storefront.v1.orders.track'],
    'storefront.account' => ['api.storefront.v1.me.show', 'api.storefront.v1.me.orders.index'],
    'storefront.account.orders' => ['api.storefront.v1.me.orders.index'],
    'storefront.account.order' => ['api.storefront.v1.me.orders.show'],
    'storefront.account.profile' => ['api.storefront.v1.me.show', 'api.storefront.v1.me.consents.index'],
    'storefront.account.addresses' => ['api.storefront.v1.me.addresses.index'],
    'storefront.account.addresses.create' => ['api.storefront.v1.address.provinces', 'api.storefront.v1.address.wards'],
    'storefront.account.addresses.edit' => ['api.storefront.v1.me.addresses.index', 'api.storefront.v1.address.wards'],
    'storefront.account.delete' => ['api.storefront.v1.me.deletion'],
    // Plugin (route chỉ có khi plugin bật).
    'storefront.p.vani-bundles.index' => ['api.storefront.x.vani-bundles.index'],
    'storefront.p.vani-cms.blog' => ['api.storefront.x.vani-cms.posts.index', 'api.storefront.x.vani-cms.categories.index'],
    'storefront.p.vani-cms.post' => ['api.storefront.x.vani-cms.posts.show'],
    'storefront.p.vani-cms.page' => ['api.storefront.x.vani-cms.pages.show'],
    'storefront.p.vani-lookbook.index' => ['api.storefront.x.vani-lookbook.lookbooks.index'],
    'storefront.p.vani-lookbook.show' => ['api.storefront.x.vani-lookbook.lookbooks.show'],
    'storefront.p.vani-wishlist.account.index' => ['api.storefront.x.vani-wishlist.items.index'],
];

/**
 * Trang GET chỉ có trên web — mỗi mục có lý do (ADR-044 §3: phần trình bày web, form thuần, link trong email).
 *
 * @var array<string, string>
 */
const STOREFRONT_WEB_ONLY_PAGES = [
    'storefront.p.vani-wishlist.guest' => 'Danh sách của trình duyệt đọc từ localStorage, thẻ sản phẩm lấy từ catalog công khai.',
    'storefront.session' => '"đường ống" của Blade khi trang cache CDN (CSRF, flash, lỗi form) — không phải dữ liệu cửa hàng',
    'storefront.robots' => 'trình bày web (SEO)',
    'storefront.sitemap' => 'trình bày web (SEO)',
    'storefront.sitemap.page' => 'trình bày web (SEO)',
    'storefront.account.login' => 'form thuần; app gọi POST /auth/login (và GET /auth/social cho nút đăng nhập mạng xã hội)',
    'storefront.account.register' => 'form thuần; app gọi POST /auth/register',
    'storefront.account.register.otp' => 'form nhập mã thuần (SĐT trong phiên); app gọi POST /auth/register/verify-otp',
    'storefront.account.password.forgot' => 'form thuần; app gọi POST /auth/password/forgot',
    'storefront.account.password.reset' => 'link trong email mở form; app gọi POST /auth/password/reset',
    'storefront.account.register.verify' => 'link trong email; app gọi POST /auth/register/verify',
    'storefront.account.email.verify' => 'link trong email; app gọi POST /auth/email/verify',
    'storefront.account.unsubscribe' => 'link huỷ nhận tin trong email marketing (có chữ ký, không cần đăng nhập) chỉ hỏi lại; app tắt đồng ý qua PUT /me/consents',
    'storefront.payment.return' => 'điểm cổng thanh toán trả khách về (HandlesReturn): chỉ chuyển hướng tới trang đơn kèm thông báo, không hiện dữ liệu, không ghi nhận thanh toán; app hỏi GET /payments/{payment}',
];

it('AF2: mọi trang Blade GET có endpoint Storefront API GET cho cùng dữ liệu, hoặc ghi lý do chỉ có trên web', function () {
    $missing = [];
    foreach (app('router')->getRoutes()->getRoutes() as $route) {
        $name = (string) $route->getName();
        $locale = '/^storefront\.('.implode('|', array_map('preg_quote', StoreLocale::supported())).')\./';
        if (! str_starts_with($name, 'storefront.') || preg_match($locale, $name) === 1 || ! in_array('GET', $route->methods(), true)) {
            continue;
        }
        if (! array_key_exists($name, STOREFRONT_API_READ_PAIRS) && ! array_key_exists($name, STOREFRONT_WEB_ONLY_PAGES)) {
            $missing[] = $name;
        }
    }

    expect($missing)->toBe([], 'Trang Blade mới: thêm endpoint API GET trước rồi khai báo ở STOREFRONT_API_READ_PAIRS (hoặc lý do ở STOREFRONT_WEB_ONLY_PAGES)');
});

it('AF2: bảng trang Blade ↔ API đọc trỏ tới route GET có thật', function () {
    $routes = app('router')->getRoutes();
    $broken = [];
    foreach (STOREFRONT_API_READ_PAIRS as $blade => $apis) {
        $plugin = str_starts_with($blade, 'storefront.p.');
        if ($routes->getByName($blade) === null) {
            if (! $plugin) {
                $broken[] = "{$blade}: không có route Blade";
            }

            continue;
        }
        foreach ($apis as $api) {
            $route = $routes->getByName($api);
            if ($route === null || ! in_array('GET', $route->methods(), true)) {
                $broken[] = "{$blade} → {$api}: không có route GET";
            }
        }
    }

    expect($broken)->toBe([])
        ->and(array_values(array_intersect(array_keys(STOREFRONT_API_READ_PAIRS), array_keys(STOREFRONT_WEB_ONLY_PAGES))))->toBe([]);
});

it('R37: JS và view của theme/plugin không gọi endpoint JSON riêng ngoài Storefront API', function () {
    // Ngoại lệ duy nhất: /_vani/phien — "đường ống" của Blade khi trang được cache CDN (CSRF của form, flash, lỗi form lần
    // gửi trước); không phải dữ liệu cửa hàng (ADR-044).
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
