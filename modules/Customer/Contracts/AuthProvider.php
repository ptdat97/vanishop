<?php

declare(strict_types=1);

namespace Modules\Customer\Contracts;

use Modules\Customer\Contracts\Data\ExternalIdentity;

/**
 * Extension point (tag `vani.customer.auth_providers`, ADR-030 §4.E): đăng nhập bằng tài khoản bên ngoài (Zalo,
 * Google, Facebook…). Plugin lo OAuth với nhà cung cấp; Core lo `state` một lần, danh sách `redirect_uri` cho phép,
 * ghép danh tính với khách, cấp token Bearer.
 *
 * Quy tắc ghép (Core): danh tính đã liên kết → đăng nhập; chưa liên kết → theo SĐT **đã xác minh** (tạo khách nếu
 * chưa có) hoặc email **đã xác minh** của khách đang có; không có cả hai → từ chối (khách xác minh SĐT bằng OTP).
 */
interface AuthProvider
{
    public const TAG = 'vani.customer.auth_providers';

    /** Mã ổn định, dùng trong URL: `/auth/social/{code}/…` */
    public function code(): string;

    public function label(): string;

    /**
     * URL chuyển khách sang nhà cung cấp. Phải gửi kèm `$state` (Core kiểm tra khi quay về).
     */
    public function authorizationUrl(string $state, string $redirectUri): string;

    /**
     * Đổi tham số callback (vd. `code`) lấy danh tính. Gọi mạng được (có timeout); thất bại → AuthProviderFailed.
     *
     * @param  array<string, string>  $params
     *
     * @throws AuthProviderFailed
     */
    public function resolve(array $params, string $redirectUri): ExternalIdentity;
}
