<?php

declare(strict_types=1);

namespace Modules\Shared\Support;

use LogicException;

/**
 * Lời gọi ra dịch vụ ngoài nằm trong transaction DB (ExternalCalls::guard, ngoài production) — lỗi lập trình: nơi bắt lỗi
 * của cổng/hãng phải ném lại lỗi này, không biến nó thành "gọi thất bại".
 */
final class ExternalCallInTransaction extends LogicException {}
