<?php

declare(strict_types=1);

namespace Modules\Payment\Contracts;

use RuntimeException;

/**
 * Callback sai chữ ký, thiếu trường, hoặc cổng không hỗ trợ callback. Không bao giờ ghi nhận thanh toán.
 */
final class InvalidCallback extends RuntimeException {}
