<?php

declare(strict_types=1);

namespace App\Logging;

use Illuminate\Log\Logger;

/**
 * `tap` của channel single/daily/monthly: gắn ContextProcessor vào logger. Laravel gọi tap với Logger (không phải
 * LogRecord), và khoá `processors` chỉ có hiệu lực với driver `monolog`.
 */
final class AttachContextProcessor
{
    public function __invoke(Logger $logger): void
    {
        $logger->pushProcessor(new ContextProcessor);
    }
}
