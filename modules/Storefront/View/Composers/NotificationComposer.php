<?php

declare(strict_types=1);

namespace Modules\Storefront\View\Composers;

use Illuminate\Session\SessionManager;
use Illuminate\View\View;

/**
 * `theme::partials.notification`: `$notifications` = `messages` truyền vào, hoặc flash phiên (`status` → thành công,
 * `error` → lỗi) khi `fromFlash` (mặc định) — trừ trang cache CDN (HTML giống nhau mọi khách; JS điền từ /_vani/phien).
 * Mỗi mục có `id` riêng, `type`, `role`, `message`, `dismissible`.
 */
final class NotificationComposer
{
    public function __construct(private readonly SessionManager $session) {}

    public function compose(View $view): void
    {
        $data = $view->getData();
        $dismissible = (bool) ($data['dismissible'] ?? true);
        $items = array_values((array) ($data['messages'] ?? []));
        if (($data['fromFlash'] ?? true) && $items === [] && ! ($data['pageCache'] ?? false)) {
            foreach (['success' => 'status', 'error' => 'error'] as $type => $key) {
                $message = $this->session->get($key);
                if (is_string($message) && $message !== '') {
                    $items[] = ['type' => $type, 'message' => $message];
                }
            }
        }

        $view->with('notifications', array_map(fn (int $index, array $item): array => [
            'id' => "vani-notification-{$index}",
            'type' => $type = (string) ($item['type'] ?? 'info'),
            'role' => $type === 'error' ? 'alert' : 'status',
            'message' => (string) ($item['message'] ?? ''),
            'dismissible' => (bool) ($item['dismissible'] ?? $dismissible),
        ], array_keys($items), $items));
    }
}
