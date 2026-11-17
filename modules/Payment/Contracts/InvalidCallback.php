<?php

declare(strict_types=1);

namespace Modules\Payment\Contracts;

use Modules\Shared\Contracts\PluginApi;
use RuntimeException;

/**
 * Callback sai chữ ký, thiếu trường, hoặc cổng không hỗ trợ callback. Không bao giờ ghi nhận thanh toán.
 */
#[PluginApi]
final class InvalidCallback extends RuntimeException {}
