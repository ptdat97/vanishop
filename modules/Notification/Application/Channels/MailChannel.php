<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Channels;

use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Modules\Notification\Contracts\Data\OutgoingMessage;
use Modules\Notification\Contracts\Data\Recipient;
use Modules\Notification\Contracts\Data\SendResult;
use Modules\Notification\Contracts\NotificationChannel;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Kênh email của Core (văn bản thuần; gửi qua mailer mặc định của Laravel).
 */
final class MailChannel implements NotificationChannel
{
    public function code(): string
    {
        return 'mail';
    }

    public function canReach(Recipient $recipient): bool
    {
        return $recipient->email !== null && filter_var($recipient->email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function send(OutgoingMessage $message): SendResult
    {
        try {
            Mail::raw((string) $message->body, function (Message $mail) use ($message): void {
                $mail->to((string) $message->recipient->email)->subject((string) $message->subject);
                $mail->getHeaders()->addTextHeader('X-Vani-Notification', $message->idempotencyKey);
            });
        } catch (TransportExceptionInterface $exception) {
            return SendResult::retryable('mail: '.$exception->getMessage());
        }

        return SendResult::sent();
    }
}
