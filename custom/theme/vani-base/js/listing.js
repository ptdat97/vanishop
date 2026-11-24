let pendingListing;
let renderedListingUrl;

function listingLocation(url) {
    const { pathname, search } = new URL(url, window.location.href);
    return pathname + search;
}

/** Trang danh sách (tìm kiếm, danh mục, thương hiệu, lọc, phân trang) không tải lại: lấy HTML (trang cache được), thay vùng [data-vani-listing]. */
export async function listing(url, { push = true, focus = null, scrollToResults = false } = {}) {
    pendingListing?.abort();
    const current = document.querySelector('[data-vani-listing]');
    if (!current) {
        window.location.href = url;
        return;
    }
    const request = new AbortController();
    pendingListing = request;
    current.setAttribute('aria-busy', 'true');
    const status = document.querySelector('[data-vani-listing-status]');
    if (status) status.textContent = '';
    try {
        const response = await fetch(url, { credentials: 'same-origin', headers: { Accept: 'text/html' }, signal: request.signal });
        if (!response.ok) throw new Error(String(response.status));
        const page = new DOMParser().parseFromString(await response.text(), 'text/html');
        const next = page.querySelector('[data-vani-listing]');
        if (!next) throw new Error('no listing');
        if (request.signal.aborted) return;
        const scrollY = window.scrollY;
        const filters = current.querySelector('#vani-listing-filters[open]');
        filters?.close();
        current.replaceWith(next);
        if (filters) {
            next.querySelector('[data-vani-drawer-open="vani-listing-filters"]')?.focus({ preventScroll: true });
        } else if (focus === 'sort') {
            next.querySelector('#vani-listing-sort')?.focus({ preventScroll: true });
        } else if (focus === 'results') {
            next.focus({ preventScroll: true });
        }
        const heading = page.querySelector('h1');
        if (heading && document.querySelector('h1')) document.querySelector('h1').textContent = heading.textContent;
        document.title = page.title || document.title;
        if (push) history.pushState(null, '', url);
        renderedListingUrl = listingLocation(url);
        if (scrollToResults) next.scrollIntoView({ block: 'start' });
        else if (push) window.scrollTo({ top: scrollY, behavior: 'instant' });
        if (status) status.textContent = next.querySelector('[data-vani-listing-count]')?.textContent.trim() ?? '';
        document.dispatchEvent(new CustomEvent('vani:content-updated', { detail: { element: next } }));
    } catch {
        if (!request.signal.aborted) window.location.href = url;
    } finally {
        if (pendingListing === request) {
            pendingListing = undefined;
            current.removeAttribute('aria-busy');
        }
    }
}

export function mountListingNavigation() {
    // Danh sách sản phẩm: lọc và phân trang không tải lại trang (link thường cho SEO; mở tab mới/ctrl+click giữ nguyên).
    document.addEventListener('click', (event) => {
        const link = event.target.closest('[data-vani-listing] a[href]');
        if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || link.target) return;
        const url = new URL(link.href, window.location.href);
        if (url.origin !== window.location.origin || url.pathname !== window.location.pathname || url.hash) return;
        event.preventDefault();
        listing(url.href, { focus: 'results', scrollToResults: Boolean(link.closest('[data-vani-pagination]')) });
    });
    const submit = (form) => {
        const url = new URL(form.action);
        url.search = new URLSearchParams([...new FormData(form)].filter(([, value]) => value !== '')).toString();
        listing(url.href, { focus: form.matches('[data-vani-sort]') ? 'sort' : 'results' });
    };
    document.addEventListener('change', (event) => {
        if (event.target.matches('#vani-listing-sort')) submit(event.target.form);
    });
    document.addEventListener('submit', (event) => {
        if (!event.target.matches('[data-vani-sort], [data-vani-listing-range]') || event.defaultPrevented) return;
        event.preventDefault();
        submit(event.target);
    });
    if (document.querySelector('[data-vani-listing]')) {
        renderedListingUrl = listingLocation(window.location.href);
        window.addEventListener('popstate', () => {
            if (listingLocation(window.location.href) === renderedListingUrl) {
                pendingListing?.abort();
                return;
            }
            listing(window.location.href, { push: false });
        });
    }
}
