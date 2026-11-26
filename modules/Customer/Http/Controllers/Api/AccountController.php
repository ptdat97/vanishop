<?php

declare(strict_types=1);

namespace Modules\Customer\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Customer\Application\AccountLifecycle;
use Modules\Customer\Application\CustomerService;
use Modules\Customer\Contracts\CustomerAccounts;
use Modules\Customer\Contracts\CustomerRejected;
use Modules\Customer\Contracts\CustomerSessions;
use Modules\Customer\Contracts\Requests\AddressRequest;
use Modules\Customer\Contracts\Requests\ConsentRequest;
use Modules\Customer\Contracts\Requests\DeleteAccountRequest;
use Modules\Customer\Contracts\Requests\PasswordRequest;
use Modules\Customer\Contracts\Requests\ProfileRequest;
use Modules\Customer\Http\Middleware\AuthenticateCustomer;

/**
 * /me — tài khoản của khách đã đăng nhập.
 */
final class AccountController
{
    public function show(Request $request): JsonResponse
    {
        return response()->json(['data' => CustomerService::toData(AuthenticateCustomer::customer($request))->toArray()]);
    }

    public function update(ProfileRequest $request, CustomerAccounts $accounts, CustomerSessions $auth): JsonResponse
    {
        $customer = AuthenticateCustomer::customer($request);
        $previousEmail = $customer->email;
        // Qua contract như storefront Blade (L45): hook `vani.service.customer.profile_attributes` chạy cho cả hai kênh.
        $updated = $accounts->updateProfile($customer->id, $request->validated());
        // Đổi/thêm email → gửi link xác minh tới email mới (L40).
        if ($updated->email !== null && $updated->email !== $previousEmail) {
            $auth->sendEmailVerification($customer->id);
        }

        return response()->json(['data' => $updated->toArray()]);
    }

    /** Gửi lại link xác minh email (0.5.32). 202 khi đã gửi; email đã xác minh hoặc chưa có email → 409. */
    public function sendEmailVerification(Request $request, CustomerSessions $auth): JsonResponse
    {
        $customer = AuthenticateCustomer::customer($request);
        if (! $auth->sendEmailVerification($customer->id)) {
            throw CustomerRejected::emailAlreadyVerified();
        }

        return response()->json(['data' => ['email' => $customer->email]], 202);
    }

    public function password(PasswordRequest $request, CustomerAccounts $accounts): JsonResponse
    {
        $data = $request->validated();
        $accounts->setPassword(AuthenticateCustomer::customer($request)->id, $data['current_password'] ?? null, $data['password']);

        return response()->json(null, 204);
    }

    public function addresses(Request $request, CustomerSessions $sessions): JsonResponse
    {
        return response()->json(['data' => $sessions->addresses(AuthenticateCustomer::customer($request)->id)]);
    }

    public function storeAddress(AddressRequest $request, CustomerAccounts $accounts): JsonResponse
    {
        return response()->json(['data' => $accounts->addAddress(AuthenticateCustomer::customer($request)->id, $request->toAddressData())], 201);
    }

    public function updateAddress(AddressRequest $request, int $address, CustomerAccounts $accounts): JsonResponse
    {
        return response()->json(['data' => $accounts->updateAddress(AuthenticateCustomer::customer($request)->id, $address, $request->toAddressData())]);
    }

    public function destroyAddress(Request $request, int $address, CustomerAccounts $accounts): JsonResponse
    {
        $accounts->deleteAddress(AuthenticateCustomer::customer($request)->id, $address);

        return response()->json(null, 204);
    }

    public function consents(Request $request, CustomerAccounts $accounts): JsonResponse
    {
        return response()->json(['data' => $accounts->marketingConsents(AuthenticateCustomer::customer($request)->id)]);
    }

    public function updateConsent(ConsentRequest $request, CustomerAccounts $accounts): JsonResponse
    {
        $data = $request->validated();
        $customerId = AuthenticateCustomer::customer($request)->id;
        $accounts->setMarketingConsent($customerId, $data['channel'], (bool) $data['granted'], 'storefront:account', (string) $request->ip());

        return response()->json(['data' => $accounts->marketingConsents($customerId)]);
    }

    public function export(Request $request, AccountLifecycle $lifecycle): JsonResponse
    {
        return response()->json(['data' => $lifecycle->export(AuthenticateCustomer::customer($request)->id)]);
    }

    /**
     * Xoá tài khoản = ẩn danh hoá; xác nhận bằng mật khẩu hiện tại (chưa có → `customer.password_required`).
     */
    public function destroy(DeleteAccountRequest $request, CustomerAccounts $accounts): JsonResponse
    {
        $data = $request->validated();
        $accounts->deleteAccount(AuthenticateCustomer::customer($request)->id, $data['password'] ?? null, $data['code'] ?? null);

        return response()->json(null, 204);
    }

    /**
     * Gửi mã một lần xác nhận xoá tài khoản (L40) — dùng khi tài khoản chưa có mật khẩu (đăng nhập OTP/mạng xã hội).
     */
    public function deletionCode(Request $request, CustomerAccounts $accounts): JsonResponse
    {
        return response()->json(['data' => $accounts->requestDeletionCode(AuthenticateCustomer::customer($request)->id, (string) $request->ip())]);
    }

    /**
     * Xoá được tài khoản chưa — trả danh sách việc còn dở (đơn chưa hoàn tất, đổi trả đang mở…) để app hiển thị trước khi hỏi mật khẩu.
     */
    public function deletion(Request $request, CustomerAccounts $accounts): JsonResponse
    {
        $blockers = $accounts->deletionBlockers(AuthenticateCustomer::customer($request)->id);

        return response()->json(['data' => ['deletable' => $blockers === [], 'blockers' => $blockers]]);
    }
}
