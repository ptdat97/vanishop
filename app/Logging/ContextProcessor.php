<?php

declare(strict_types=1);

namespace App\Logging;

use Illuminate\Support\Facades\Context;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Đưa Laravel Context (correlation_id) vào mọi dòng log.
 *
 * `Context::add('correlation_id', …)` trong AssignCorrelationId chỉ giữ trong bộ nhớ
 * của request/job; muốn truy vết được theo id thì id phải xuất hiện trong log.
 * Processor gắn vào channel qua `tap()`/`processors` trong config/logging.php.
 */
final class ContextProcessor implements ProcessorInterface
{
    /**
     * @param  list<string>  $keys  khoá trong Context cần đưa vào log
     */
    public function __construct(private readonly array $keys = ['correlation_id']) {}

    public function __invoke(LogRecord $record): LogRecord
    {
        $extra = $record->extra;

        foreach ($this->keys as $key) {
            $value = Context::get($key);
            if ($value !== null && ! array_key_exists($key, $extra)) {
                $extra[$key] = $value;
            }
        }

        return $record->with(extra: $extra);
    }
}
