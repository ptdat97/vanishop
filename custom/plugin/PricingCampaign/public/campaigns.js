/*
 * vani.pricing-campaign: đếm ngược ([data-vani-countdown] = mốc ISO) và số suất còn lại ở trang sản phẩm (đọc API không cache,
 * vì HTML trang có thể nằm trong cache trang). Gắn một lần cho cả trang.
 */
(() => {
    if (window.vaniPricingCampaign) {
        return;
    }
    window.vaniPricingCampaign = true;
    const script = document.querySelector('script[data-vani-campaign-script]');
    let units = { d: 'd', h: 'h', m: 'm', s: 's' };
    try {
        units = { ...units, ...JSON.parse(script?.dataset.units ?? '{}') };
    } catch {
        // giữ mặc định
    }
    const pad = (value) => String(value).padStart(2, '0');

    const tick = () => {
        document.querySelectorAll('[data-vani-countdown]').forEach((element) => {
            const left = Math.max(0, Math.floor((Date.parse(element.dataset.vaniCountdown) - Date.now()) / 1000));
            const days = Math.floor(left / 86400);
            const text = `${days > 0 ? `${days} ${units.d} ` : ''}${pad(Math.floor((left % 86400) / 3600))}:${pad(Math.floor((left % 3600) / 60))}:${pad(left % 60)}`;
            if (element.textContent !== text) {
                element.textContent = text;
            }
        });
    };
    tick();
    setInterval(tick, 1000);

    document.querySelectorAll('[data-vani-campaign-offer][data-live-url]').forEach((box) => {
        fetch(box.dataset.liveUrl, { headers: { Accept: 'application/json' } })
            .then((response) => (response.ok ? response.json() : null))
            .then((json) => {
                const offer = json?.data;
                const remaining = box.querySelector('[data-vani-campaign-remaining]');
                if (!offer?.running || offer.remaining === null || !remaining) {
                    return;
                }
                remaining.textContent = offer.remaining === 0 ? script.dataset.soldOut : script.dataset.remaining.replace(':count', offer.remaining);
                const bar = box.querySelector('[data-vani-campaign-progress]');
                if (bar && offer.sold_percent !== null) {
                    bar.style.width = `${offer.sold_percent}%`;
                    bar.parentElement.setAttribute('aria-valuenow', offer.sold_percent);
                }
            })
            .catch(() => {});
    });
})();
