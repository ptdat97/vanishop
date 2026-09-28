<?php

declare(strict_types=1);

namespace Modules\Storefront\Application;

use Modules\Payment\Contracts\Data\PaymentView;

final class PaymentPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function present(PaymentView $view): array
    {
        return [
            'id' => $view->publicId,
            'method' => $view->gatewayCode,
            'status' => $view->status,
            'amount' => ['amount' => $view->amount, 'currency' => $view->currencyCode],
            'expires_at' => $view->expiresAt,
            'action' => $view->action === null ? null : [
                'type' => $view->action->type,
                'url' => $view->action->url,
                'qr' => $view->action->qrPayload,
                'instructions' => $view->action->instructions === [] ? null : $view->action->instructions,
            ],
            'error' => $view->actionError,
            'order' => ['id' => $view->orderPublicId, 'number' => $view->orderNumber],
        ];
    }
}
