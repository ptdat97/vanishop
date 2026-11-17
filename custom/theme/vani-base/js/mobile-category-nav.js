/*
 * Split Category Navigation trong menu di động (theme::partials.mobile-menu-content).
 *
 * Hai nguồn, chuẩn hoá về cùng một node `{key, name, url, badge, image_url, banner, navigate, children}`:
 * - Menu "Di động" của Admin (đã phân giải ở server, nhúng ở `script[data-cnav-items]`): ảnh cấp 1 = banner, ảnh cấp sâu
 *   hơn = ô lưới; mục cấp 1 không có con mà có link → bấm là mở link (Lookbook, Tin tức…).
 * - Chưa có menu → cây danh mục Catalog qua Storefront API (`data-source`, X-Vani-Locale), tải lần đầu mở drawer.
 * Markup lấy từ <template data-cnav-template="…"> do Blade render (theme con override được); JS chỉ điền chữ bằng
 * textContent, link và ảnh.
 *
 * Nhóm nội dung chỉ dựa vào cấu trúc cây (dùng chung mọi ngành hàng):
 * - Cấp 2 có con → mỗi mục cấp 2 là một nhóm, lưới là các con; cấp 2 không con gom vào nhóm đầu mang tên mục cấp 1.
 * - Không mục cấp 2 nào có con → một nhóm là lưới cấp 2.
 * - Không có con → trạng thái trống (+ link "Xem tất cả" nếu mục có link).
 */

const SLUG_PLACEHOLDER = '__vani_slug__';

/** Chia nội dung cột phải của một mục cấp 1 thành các nhóm {title, node|null, items}. */
export function sectionsFor(node) {
    const children = node.children ?? [];
    const grouped = children.filter((child) => (child.children ?? []).length > 0);
    if (grouped.length === 0) {
        return children.length === 0 ? [] : [{ title: node.name, node: null, items: children }];
    }
    const leaves = children.filter((child) => (child.children ?? []).length === 0);
    const sections = leaves.length === 0 ? [] : [{ title: node.name, node: null, items: leaves }];

    return [...sections, ...grouped.map((child) => ({ title: child.name, node: child, items: child.children }))];
}

/** Menu Admin (`{label, url, badge, image_url, children}`) → node. */
export function fromMenu(items, path = 'm', level = 1) {
    return (items ?? []).map((item, index) => {
        const key = `${path}${index}`;
        const children = fromMenu(item.children, `${key}-`, level + 1);
        return {
            key,
            name: item.label ?? '',
            url: item.url ?? null,
            badge: item.badge ?? null,
            image_url: level === 1 ? null : (item.image_url ?? null),
            banner: level === 1 && item.image_url ? { image_url: item.image_url } : null,
            navigate: level === 1 && children.length === 0 && Boolean(item.url),
            children,
        };
    });
}

/** Cây danh mục Catalog (Storefront API) → node. */
export function fromCatalog(nodes, categoryUrl) {
    return (nodes ?? []).map((node) => ({
        key: String(node.id),
        name: node.name ?? '',
        url: categoryUrl(node.slug),
        badge: null,
        image_url: node.image_url ?? null,
        banner: node.banner ?? null,
        navigate: false,
        children: fromCatalog(node.children, categoryUrl),
    }));
}

/** Link hợp lệ để gắn vào href (http(s), cùng site, mailto/tel) — chặn `javascript:` và giá trị lạ. */
const safeUrl = (value) => (typeof value === 'string' && /^(https?:\/\/|\/(?!\/)|mailto:|tel:)/i.test(value.trim()) ? value : null);

export function mountMobileCategoryNav(root) {
    if (!root || root.dataset.state === 'ready' || root.dataset.state === 'loading') return;

    const tabList = root.querySelector('[data-cnav-tabs]');
    const sidebar = root.querySelector('[data-cnav-sidebar]');
    const content = root.querySelector('[data-cnav-content]');
    const panelsBox = root.querySelector('[data-cnav-panels]');
    const skeletons = [...root.querySelectorAll('[data-cnav-skeleton]')];
    const errorBox = root.querySelector('[data-cnav-error]');
    const emptyStore = root.querySelector('[data-cnav-empty-store]');
    const panels = new Map();
    let tabs = [];

    const template = (name) => root.querySelector(`template[data-cnav-template="${name}"]`)?.content.firstElementChild?.cloneNode(true) ?? null;
    const categoryUrl = (slug) => root.dataset.categoryUrl.replace(SLUG_PLACEHOLDER, encodeURIComponent(slug));
    const setText = (element, selector, text) => {
        const target = element.querySelector(selector);
        if (target) target.textContent = text ?? '';
    };
    const setLink = (element, url) => {
        const href = safeUrl(url);
        if (element && href) element.setAttribute('href', href);
    };
    const showBadge = (element, badge) => {
        if (badge) element.querySelector(`[data-cnav-badge="${badge}"]`)?.removeAttribute('hidden');
    };

    function showImage(thumb, image, url, fallbackText) {
        const initial = thumb.querySelector('[data-cnav-initial]');
        const fallback = () => {
            image.remove();
            if (initial) {
                initial.textContent = (fallbackText ?? '').trim().charAt(0);
                initial.hidden = false;
            }
        };
        if (!safeUrl(url)) {
            fallback();
            return;
        }
        thumb.classList.add('animate-pulse');
        const done = () => thumb.classList.remove('animate-pulse');
        image.addEventListener('load', done, { once: true });
        image.addEventListener('error', () => {
            done();
            fallback();
        }, { once: true });
        image.src = url;
    }

    function buildItem(node) {
        const item = template('item');
        if (!item) return null;
        setLink(item.querySelector('[data-cnav-link]'), node.url);
        setText(item, '[data-cnav-name]', node.name);
        showBadge(item, node.badge);
        const thumb = item.querySelector('[data-cnav-thumb]');
        const image = item.querySelector('[data-cnav-image]');
        if (thumb && image) showImage(thumb, image, node.image_url, node.name);
        return item;
    }

    function buildBanner(banner, node) {
        const element = template('banner');
        const image = element?.querySelector('[data-cnav-image]');
        if (!element || !image) return null;
        setLink(element, banner.url ?? node.url);
        image.alt = banner.alt ?? banner.title ?? node.name ?? '';
        image.src = banner.image_url;
        if (banner.title || banner.subtitle) {
            setText(element, '[data-cnav-banner-title]', banner.title);
            setText(element, '[data-cnav-banner-subtitle]', banner.subtitle);
            element.querySelector('[data-cnav-banner-text]')?.removeAttribute('hidden');
        }
        return element;
    }

    function buildPanel(node, tab) {
        const panel = template('panel');
        panel.id = `${tab.id}-panel`;
        panel.setAttribute('aria-labelledby', tab.id);

        if (safeUrl(node.banner?.image_url)) {
            const banner = buildBanner(node.banner, node);
            if (banner) panel.append(banner);
        }

        const sections = sectionsFor(node);
        sections.forEach((section) => {
            const element = template('section');
            if (!element) return;
            const title = element.querySelector('[data-cnav-section-title]');
            if (title) {
                title.textContent = section.title ?? '';
                setLink(title, section.node?.url);
            }
            const grid = element.querySelector('[data-cnav-grid]');
            section.items.forEach((child) => {
                const item = buildItem(child);
                if (item && grid) grid.append(item);
            });
            panel.append(element);
        });
        if (sections.length === 0) {
            const empty = template('empty');
            if (empty) panel.append(empty);
        }

        const all = safeUrl(node.url) ? template('all') : null;
        if (all) {
            setLink(all.querySelector('[data-cnav-link]'), node.url);
            setText(all, '[data-cnav-name]', node.name);
            panel.append(all);
        }
        return panel;
    }

    function centerTab(tab, behavior = 'smooth') {
        if (!sidebar || !tab) return;
        sidebar.scrollTo({ top: tab.offsetTop - (sidebar.clientHeight - tab.offsetHeight) / 2, behavior });
    }

    function select(tab, { focus = false, behavior = 'smooth' } = {}) {
        tabs.forEach((item) => {
            const active = item === tab;
            item.setAttribute('aria-selected', String(active));
            item.tabIndex = active ? 0 : -1;
        });
        const key = tab.dataset.key;
        if (!panels.has(key)) {
            const panel = buildPanel(tab.vaniNode, tab);
            panels.set(key, panel);
            panelsBox.append(panel);
        }
        panels.forEach((panel, panelKey) => {
            panel.hidden = panelKey !== key;
        });
        if (content) content.scrollTop = 0;
        centerTab(tab, behavior);
        if (focus) tab.focus();
    }

    function render(nodes) {
        setLoading(false);
        tabList.replaceChildren();
        panelsBox.replaceChildren();
        panels.clear();
        tabs = nodes.map((node) => {
            const tab = template('tab');
            tab.id = `vani-cnav-tab-${node.key}`;
            tab.dataset.key = node.key;
            tab.setAttribute('aria-controls', `${tab.id}-panel`);
            tab.vaniNode = node;
            setText(tab, '[data-cnav-name]', node.name);
            showBadge(tab, node.badge);
            tabList.append(tab);
            return tab;
        });
        if (emptyStore) emptyStore.hidden = tabs.length > 0;
        // Mục đầu tiên có nội dung (không phải link thẳng) được mở sẵn.
        const first = tabs.find((tab) => !tab.vaniNode.navigate) ?? tabs[0];
        if (first) select(first, { behavior: 'instant' });
        root.dataset.state = 'ready';
    }

    function setLoading(loading) {
        skeletons.forEach((skeleton) => {
            skeleton.hidden = !loading;
        });
    }

    async function load() {
        root.dataset.state = 'loading';
        if (errorBox) errorBox.hidden = true;
        setLoading(true);
        try {
            const response = await fetch(root.dataset.source, { headers: { Accept: 'application/json', 'X-Vani-Locale': root.dataset.locale || '' } });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const json = await response.json();
            render(fromCatalog(Array.isArray(json.data) ? json.data : [], categoryUrl));
        } catch (error) {
            console.error('Không tải được danh mục.', error);
            setLoading(false);
            if (errorBox) errorBox.hidden = false;
            root.dataset.state = 'error';
        }
    }

    if (!root.dataset.cnavBound) {
        root.dataset.cnavBound = '1';
        root.addEventListener('click', (event) => {
            const tab = event.target.closest('[role="tab"]');
            if (tab && tabs.includes(tab)) {
                const node = tab.vaniNode;
                if (node.navigate && safeUrl(node.url)) {
                    window.location.assign(node.url);
                } else if (tab.getAttribute('aria-selected') !== 'true') {
                    select(tab);
                }
                return;
            }
            if (event.target.closest('[data-cnav-retry]')) load();
        });
        // Bàn phím cho tablist dọc: ↑ ↓ Home End (Enter/Space = bấm).
        root.addEventListener('keydown', (event) => {
            const index = tabs.indexOf(event.target);
            if (index < 0) return;
            const next = { ArrowDown: index + 1, ArrowUp: index - 1, Home: 0, End: tabs.length - 1 }[event.key];
            if (next === undefined) return;
            event.preventDefault();
            const tab = tabs[(next + tabs.length) % tabs.length];
            if (tab.vaniNode.navigate) {
                tab.focus();
            } else {
                select(tab, { focus: true });
            }
        });
    }

    const embedded = root.querySelector('script[data-cnav-items]');
    if (embedded) {
        let items = [];
        try {
            items = JSON.parse(embedded.textContent || '[]');
        } catch {
            items = [];
        }
        render(fromMenu(Array.isArray(items) ? items : []));
        return;
    }
    load();
}
