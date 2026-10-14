<script type="application/json" id="vani-tracking-config">@json($config)</script>
<script>
(function () {
    'use strict';
    var cfg = JSON.parse(document.getElementById('vani-tracking-config').textContent);
    var queue = [];
    var loaded = false;
    var metaNames = { view_item: 'ViewContent', search: 'Search', add_to_cart: 'AddToCart', begin_checkout: 'InitiateCheckout', add_payment_info: 'AddPaymentInfo', purchase: 'Purchase' };

    function readCookie(name) {
        var match = document.cookie.match('(?:^|; )' + name + '=([^;]*)');
        return match ? decodeURIComponent(match[1]) : null;
    }
    function rememberConsent(value) {
        document.cookie = cfg.cookie + '=' + value + '; path=/; max-age=' + 180 * 86400 + '; SameSite=Lax' + (location.protocol === 'https:' ? '; Secure' : '');
    }
    function granted() {
        if (cfg.consent === 'none') return true;
        if (cfg.consent === 'custom') {
            try {
                if (cfg.consent_expression && Function('return (' + cfg.consent_expression + ')')()) {
                    if (readCookie(cfg.cookie) !== 'granted') rememberConsent('granted');
                    return true;
                }
            } catch (error) {
                // biểu thức lỗi: coi như chưa đồng ý
            }
            return false;
        }
        return readCookie(cfg.cookie) === 'granted';
    }
    function addScript(src) {
        var script = document.createElement('script');
        script.async = true;
        script.src = src;
        document.head.appendChild(script);
    }
    function load() {
        if (loaded) return;
        loaded = true;
        window.dataLayer = window.dataLayer || [];
        if (cfg.gtm) {
            window.dataLayer.push({ 'gtm.start': Date.now(), event: 'gtm.js' });
            addScript('https://www.googletagmanager.com/gtm.js?id=' + encodeURIComponent(cfg.gtm));
        }
        if (cfg.ga4) {
            window.gtag = window.gtag || function () { window.dataLayer.push(arguments); };
            window.gtag('js', new Date());
            window.gtag('config', cfg.ga4);
            addScript('https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(cfg.ga4));
        }
        if (cfg.meta && !window.fbq) {
            var fbq = window.fbq = function () { fbq.callMethod ? fbq.callMethod.apply(fbq, arguments) : fbq.queue.push(arguments); };
            if (!window._fbq) window._fbq = fbq;
            fbq.push = fbq; fbq.loaded = true; fbq.version = '2.0'; fbq.queue = [];
            addScript('https://connect.facebook.net/en_US/fbevents.js');
            fbq('init', cfg.meta);
            fbq('track', 'PageView');
        }
    }
    function send(name, data) {
        var onceKey = data.event_id ? 'vani_tp_' + data.event_id : null;
        try {
            if (onceKey && localStorage.getItem(onceKey)) return;
            if (onceKey) localStorage.setItem(onceKey, '1');
        } catch (error) {
            // localStorage bị chặn: vẫn gửi
        }
        if (cfg.debug) console.info('[vani.tracking-pixels]', name, data);
        if (cfg.ga4 && window.gtag) window.gtag('event', name, data);
        if (cfg.gtm) {
            window.dataLayer.push({ ecommerce: null });
            window.dataLayer.push({ event: name, ecommerce: data });
        }
        if (cfg.meta && window.fbq && metaNames[name]) {
            var items = data.items || [];
            var params = { currency: data.currency, value: data.value, content_type: 'product',
                content_ids: items.map(function (item) { return item.item_id; }),
                contents: items.map(function (item) { return { id: item.item_id, quantity: item.quantity }; }) };
            if (data.search_term) params.search_string = data.search_term;
            window.fbq('track', metaNames[name], params, data.event_id ? { eventID: data.event_id } : undefined);
        }
    }
    function track(name, data) {
        if (granted()) {
            load();
            send(name, data);
        } else {
            queue.push([name, data]);
        }
    }
    function flush() {
        load();
        queue.splice(0).forEach(function (entry) { send(entry[0], entry[1]); });
    }

    // Giỏ đổi (theme phát sau thêm/sửa/xoá dòng) — đăng ký ngay vì theme phát khi trang vừa tải.
    document.addEventListener('vani:cart-updated', function (event) {
        var change = event.detail || {};
        var price = (change.unit_amount || 0) / Math.pow(10, cfg.currency_exponent);
        track(change.action === 'add' ? 'add_to_cart' : 'remove_from_cart', {
            currency: change.currency, value: price * change.quantity,
            items: [{ item_id: change.sku || String(change.variant_id), item_name: change.name, price: price, quantity: change.quantity }],
        });
    });
    // Chọn phương thức thanh toán: gửi khi khách bấm đặt hàng.
    document.addEventListener('submit', function (event) {
        var method = event.target.querySelector && event.target.querySelector('input[name="payment_method"]:checked');
        if (method) track('add_payment_info', { payment_type: method.value });
    }, true);

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('script[data-vani-track]').forEach(function (node) {
            try { track(node.getAttribute('data-vani-track'), JSON.parse(node.textContent)); } catch (error) { /* dữ liệu hỏng */ }
        });
        var banner = document.querySelector('[data-vani-consent]');
        if (!banner) return;
        if (readCookie(cfg.cookie) === null) banner.hidden = false;
        banner.querySelector('[data-vani-consent-accept]').addEventListener('click', function () {
            rememberConsent('granted');
            banner.hidden = true;
            flush();
        });
        banner.querySelector('[data-vani-consent-deny]').addEventListener('click', function () {
            rememberConsent('denied');
            banner.hidden = true;
            queue.length = 0;
        });
    });
})();
</script>
