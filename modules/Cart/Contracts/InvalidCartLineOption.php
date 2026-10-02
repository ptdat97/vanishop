<?php

declare(strict_types=1);

namespace Modules\Cart\Contracts;

use InvalidArgumentException;

/**
 * CartLineOption từ chối tuỳ chọn (thông báo hiển thị cho khách). Core đổi thành CartRejected `cart.option_invalid`.
 */
final class InvalidCartLineOption extends InvalidArgumentException {}
