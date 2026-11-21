import { mountMobileCategoryNav } from './mobile-category-nav.js';

export function mountAccountMenu() {
    const accountMenuRoot = document.querySelector('[data-vani-account-menu-root]');
    if (accountMenuRoot) {
        Promise.all([import('vue'), import('./AccountMenuDropdown.vue')])
            .then(([{ createApp }, { default: AccountMenuDropdown }]) => {
                const links = accountMenuRoot.querySelector('[data-vani-account-menu-links]');
                if (!links) return;

                createApp(AccountMenuDropdown, {
                    label: accountMenuRoot.dataset.label ?? '',
                    linksHtml: links.outerHTML,
                }).mount(accountMenuRoot);
            })
            .catch((error) => console.error('Không thể tải menu tài khoản.', error));
    }
}

export function mountDialogs() {
    const dialogOpeners = new WeakMap();
    document.addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) return;
        const opener = event.target.closest('[data-vani-drawer-open], [data-vani-modal-open]');
        if (opener) {
            const dialogId = opener.dataset.vaniDrawerOpen || opener.dataset.vaniModalOpen;
            const dialog = document.getElementById(dialogId);
            if (dialog instanceof HTMLDialogElement && !dialog.open) {
                event.preventDefault();
                dialogOpeners.set(dialog, opener);
                dialog.showModal();
                // Menu di động: tải danh mục (Storefront API) ở lần mở đầu; lỗi thì lần mở sau thử lại.
                if (dialog.matches('[data-vani-mobile-menu-dialog]')) {
                    mountMobileCategoryNav(dialog.querySelector('[data-vani-category-nav]'));
                }
            }
            return;
        }

        const closeButton = event.target.closest('[data-vani-drawer-close]');
        if (closeButton) {
            closeButton.closest('dialog.vani-drawer')?.close();
            return;
        }

        const modalCloseButton = event.target.closest('[data-vani-modal-close]');
        if (modalCloseButton) {
            modalCloseButton.closest('dialog.vani-modal')?.close();
            return;
        }

        if (event.target instanceof HTMLDialogElement && event.target.matches('.vani-drawer, .vani-modal')) {
            event.target.close();
        }
    });
    document.addEventListener('close', (event) => {
        if (!(event.target instanceof HTMLDialogElement) || !event.target.matches('.vani-drawer, .vani-modal')) return;
        dialogOpeners.get(event.target)?.focus();
        dialogOpeners.delete(event.target);
    }, true);
}

// Ô tìm kiếm: gợi ý sản phẩm, danh mục, thương hiệu khi gõ ≥ 2 ký tự (Storefront API). Dựng bằng DOM, không chèn HTML.
export function mountSearchSuggestions(t) {
    document.querySelectorAll('form[data-vani-suggest]').forEach((form) => {
        const input = form.querySelector('input[name="q"]');
        const box = form.querySelector('[data-vani-suggest-box]');
        if (!input || !box) return;
        let timer;
        let controller;
        const group = (title, items, render) => {
            if (!items.length) return [];
            const heading = document.createElement('p');
            heading.className = 'px-2 pt-2 text-xs font-medium uppercase text-slate-500';
            heading.textContent = title;
            return [heading, ...items.map(render)];
        };
        const link = (item, label) => {
            const anchor = document.createElement('a');
            anchor.href = item.url;
            anchor.className = 'flex items-center gap-2 rounded px-2 py-1.5 hover:bg-slate-100';
            if (item.image_url) {
                const image = document.createElement('img');
                image.src = item.image_url;
                image.alt = '';
                image.className = 'h-8 w-8 rounded object-cover';
                anchor.append(image);
            }
            anchor.append(document.createTextNode(label ?? item.name ?? ''));
            return anchor;
        };
        const cancelPending = () => {
            clearTimeout(timer);
            controller?.abort();
        };
        const close = () => {
            cancelPending();
            box.hidden = true;
            box.replaceChildren();
        };
        input.addEventListener('input', () => {
            const query = input.value.trim();
            if (query.length < 2) return close();
            cancelPending();
            timer = setTimeout(() => {
                const request = new AbortController();
                controller = request;
                fetch(`${form.dataset.vaniSuggest}?${new URLSearchParams({ q: query })}`, { headers: { Accept: 'application/json', 'X-Vani-Locale': form.dataset.locale || '' }, signal: request.signal })
                    .then((response) => (response.ok ? response.json() : null))
                    .then((json) => {
                        if (request.signal.aborted) return;
                        const data = json?.data;
                        if (!data) return close();
                        const nodes = [
                            ...group(t('suggest_products'), data.products, (item) => link(item)),
                            ...group(t('suggest_categories'), data.categories, (item) => link(item)),
                            ...group(t('suggest_brands'), data.brands, (item) => link(item)),
                        ];
                        if (!nodes.length) return close();
                        const all = link({ url: data.search_url }, t('suggest_all'));
                        all.classList.add('mt-1', 'border-t', 'border-slate-100', 'font-medium');
                        box.replaceChildren(...nodes, all);
                        box.hidden = false;
                    })
                    .catch(() => {
                        if (!request.signal.aborted) close();
                    });
            }, 200);
        });
        input.addEventListener('keydown', (event) => event.key === 'Escape' && close());
        form.closest('dialog')?.addEventListener('close', close);
        document.addEventListener('click', (event) => !form.contains(event.target) && close());
    });
}
