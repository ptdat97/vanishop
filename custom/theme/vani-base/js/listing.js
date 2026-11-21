let pendingListing;

/** Trang danh sách (tìm kiếm, danh mục, thương hiệu, lọc, phân trang) không tải lại: lấy HTML (trang cache được), thay vùng [data-vani-listing]. */
export async function listing(url, { push = true } = {}) {
    pendingListing?.abort();
    const current = document.querySelector('[data-vani-listing]');
    if (!current) {
        window.location.href = url;
        return;
    }
    const request = new AbortController();
    pendingListing = request;
    current.setAttribute('aria-busy', 'true');
    try {
        const response = await fetch(url, { credentials: 'same-origin', headers: { Accept: 'text/html' }, signal: request.signal });
        if (!response.ok) throw new Error(String(response.status));
        const page = new DOMParser().parseFromString(await response.text(), 'text/html');
        const next = page.querySelector('[data-vani-listing]');
        if (!next) throw new Error('no listing');
        if (request.signal.aborted) return;
        const filtersWereOpen = current.querySelector('#vani-listing-filters')?.open;
        current.replaceWith(next);
        if (filtersWereOpen) {
            next.querySelector('[data-vani-drawer-open="vani-listing-filters"]')?.focus({ preventScroll: true });
        }
        const heading = page.querySelector('h1');
        if (heading && document.querySelector('h1')) document.querySelector('h1').textContent = heading.textContent;
        document.title = page.title || document.title;
        if (push) history.pushState({ vaniListing: true }, '', url);
        next.scrollIntoView({ block: 'start', behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
        document.dispatchEvent(new CustomEvent('vani:content-updated', { detail: { element: next } }));
    } catch {
        if (!request.signal.aborted) window.location.href = url;
    } finally {
        if (pendingListing === request) pendingListing = undefined;
    }
}

export function mountListingNavigation() {
    // Danh sách sản phẩm: lọc và phân trang không tải lại trang (link thường cho SEO; mở tab mới/ctrl+click giữ nguyên).
    document.addEventListener('click', (event) => {
        const link = event.target.closest('[data-vani-listing] a[href]');
        if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || link.target) return;
        const url = new URL(link.href, window.location.href);
        if (url.origin !== window.location.origin || url.pathname !== window.location.pathname) return;
        event.preventDefault();
        listing(url.href);
    });
    window.addEventListener('popstate', (event) => {
        if (event.state?.vaniListing || document.querySelector('[data-vani-listing]')) {
            listing(window.location.href, { push: false });
        }
    });
    if (document.querySelector('[data-vani-listing]')) {
        history.replaceState({ vaniListing: true }, '', window.location.href);
    }
}
