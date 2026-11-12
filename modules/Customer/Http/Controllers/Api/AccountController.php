<?php

declare(strict_types=1);

namespace Modules\Customer\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Customer\Application\AccountLifecycle;
use Modules\Customer\Application\AddressBook;
use Modules\Customer\Application\AuthService;
use Modules\Customer\Application\ConsentService;
use Modules\Customer\Application\CustomerService;
use Modules\Customer\Http\Middleware\AuthenticateCustomer;
use Modules\Customer\Http\Requests\AddressRequest;
use Modules\Customer\Http\Requests\PasswordRequest;
use Modules\Customer\Http\Requests\ProfileRequest;

/**
 * /me — tài khoản của khách đã đăng nhập.
 */
final class AccountController
{
    public function show(Request $request): JsonResponse
    {
        return response()->json(['data' => CustomerService::toData(AuthenticateCustomer::customer($request))->toArray()]);
    }

    public function update(ProfileRequest $request, CustomerService $customers): JsonResponse
    {
        return response()->json(['data' => $customers->updateProfile(AuthenticateCustomer::customer($request)->id, $request->validated())->toArray()]);
    }

    public function password(PasswordRequest $request, AuthService $auth): JsonResponse
    {
        $data = $request->validated();
        $auth->setPassword(AuthenticateCustomer::customer($request)->id, $data['current_password'] ?? null, $data['password']);

        return response()->json(null, 204);
    }

    public function addresses(Request $request, AddressBook $book): JsonResponse
    {
        return response()->json(['data' => $book->all(AuthenticateCustomer::customer($request)->id)]);
    }

    public function storeAddress(AddressRequest $request, AddressBook $book): JsonResponse
    {
        return response()->json(['data' => $book->add(AuthenticateCustomer::customer($request)->id, $request->toAddressData())], 201);
    }

    public function updateAddress(AddressRequest $request, int $address, AddressBook $book): JsonResponse
    {
        return response()->json(['data' => $book->update(AuthenticateCustomer::customer($request)->id, $address, $request->toAddressData())]);
    }

    public function destroyAddress(Request $request, int $address, AddressBook $book): JsonResponse
    {
        $book->delete(AuthenticateCustomer::customer($request)->id, $address);

        return response()->json(null, 204);
    }

    public function consents(Request $request, ConsentService $consents): JsonResponse
    {
        return response()->json(['data' => $consents->all(AuthenticateCustomer::customer($request)->id)]);
    }

    public function updateConsent(Request $request, ConsentService $consents): JsonResponse
    {
        $data = $request->validate([
            'channel' => ['required', 'string', 'regex:'.ConsentService::CHANNEL_PATTERN],
            'purpose' => ['required', Rule::in(ConsentService::PURPOSES)],
            'granted' => ['required', 'boolean'],
        ]);
        $customerId = AuthenticateCustomer::customer($request)->id;
        $consents->set($customerId, $data['channel'], $data['purpose'], (bool) $data['granted'], 'storefront:account', (string) $request->ip());

        return response()->json(['data' => $consents->all($customerId)]);
    }

    public function export(Request $request, AccountLifecycle $lifecycle): JsonResponse
    {
        return response()->json(['data' => $lifecycle->export(AuthenticateCustomer::customer($request)->id)]);
    }

    /**
     * Xoá tài khoản = ẩn danh hoá; bắt buộc OTP mục đích `delete_account` gửi tới SĐT của tài khoản.
     */
    public function destroy(Request $request, AccountLifecycle $lifecycle): JsonResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:10']]);
        $lifecycle->deleteOwnAccount(AuthenticateCustomer::customer($request)->id, $data['code']);

        return response()->json(null, 204);
    }

    /**
     * Xoá được tài khoản chưa — trả danh sách việc còn dở (đơn chưa hoàn tất, đổi trả đang mở…) để app hiển thị trước khi xin OTP.
     */
    public function deletion(Request $request, AccountLifecycle $lifecycle): JsonResponse
    {
        $blockers = $lifecycle->deletionBlockers(AuthenticateCustomer::customer($request)->id);

        return response()->json(['data' => ['deletable' => $blockers === [], 'blockers' => $blockers]]);
    }
}
