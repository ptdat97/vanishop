<?php

declare(strict_types=1);

namespace Modules\Customer\Contracts;

use Modules\Shared\Contracts\PluginApi;
use RuntimeException;

/**
 * Nhà cung cấp từ chối/không trả được danh tính (mã hết hạn, khách huỷ, lỗi mạng). Core trả `customer.social_failed`.
 */
#[PluginApi]
final class AuthProviderFailed extends RuntimeException {}
