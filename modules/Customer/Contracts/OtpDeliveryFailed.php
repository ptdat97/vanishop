<?php

declare(strict_types=1);

namespace Modules\Customer\Contracts;

use RuntimeException;

/**
 * `OtpSender::send()` ném khi không gửi được (số không dùng Zalo, nhà cung cấp lỗi…): Core thử kênh kế tiếp.
 */
final class OtpDeliveryFailed extends RuntimeException {}
