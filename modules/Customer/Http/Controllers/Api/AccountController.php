<?php

declare(strict_types=1);

namespace Modules\Customer\Http\Controllers\Api;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Customer\Application\AccountLifecycle;
use Modules\Customer\Application\AddressBook;
use Modules\Customer\Application\AuthService;
use Modules\Customer\Application\ConsentService;
use Modules\Customer\Application\CustomerService;
use Modules\Customer\Application\OtpService;
use Modules\Customer\Contracts\Data\OtpPurpose;
use Modules\Customer\Http\Middleware\AuthenticateCustomer;
use Modules\Shared\Support\Phones;

/**
 * /me — tài khoản của khách đã đăng nhập.
 */
final class AccountController
{
    public function show(Request $request): JsonResponse
    {
        return response()->json(['data' => CustomerService::toData(AuthenticateCustomer::customer($request))->toArray()]);
    }

    public function update(Request $request, CustomerService $customers): JsonResponse
    {
        $data = $request->validate([
            'full_name' => ['sometimes', 'required', 'string', 'max:120'],
            'email' => ['sometimes', 'nullable', 'email', 'max:190'],
            'birth_date' => ['sometimes', 'nullable', 'date', 'before:today', 'after:1900-01-01'],
            'gender' => ['sometimes', 'nullable', Rule::in(['female', 'male', 'other'])],
        ]);

        return response()->json(['data' => $customers->updateProfile(AuthenticateCustomer::customer($request)->id, $data)->toArray()]);
    }

    public function password(Request $request, AuthService $auth): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['nullable', 'string', 'max:200'],
            'password' => ['required', 'string', 'min:8', 'max:200', 'confirmed'],
        ]);
        $auth->setPassword(AuthenticateCustomer::customer($request)->id, $data['current_password'] ?? null, $data['password']);

        return response()->json(null, 204);
    }

    public function addresses(Request $request, AddressBook $book): JsonResponse
    {
        return response()->json(['data' => $book->all(AuthenticateCustomer::customer($request)->id)]);
    }

    public function storeAddress(Request $request, AddressBook $book): JsonResponse
    {
        return response()->json(['data' => $book->add(AuthenticateCustomer::customer($request)->id, $request->validate($this->addressRules(true)))], 201);
    }

    public function updateAddress(Request $request, int $address, AddressBook $book): JsonResponse
    {
        return response()->json(['data' => $book->update(AuthenticateCustomer::customer($request)->id, $address, $request->validate($this->addressRules(false)))]);
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
    public function destroy(Request $request, OtpService $otp, AccountLifecycle $lifecycle): JsonResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:10']]);
        $customer = AuthenticateCustomer::customer($request);
        $otp->verify((string) $customer->phone, OtpPurpose::DeleteAccount, $data['code']);
        $lifecycle->anonymize($customer->id, 'customer_request');

        return response()->json(null, 204);
    }

    /**
     * @return array<string, list<mixed>>
     */
    private function addressRules(bool $creating): array
    {
        $required = $creating ? 'required' : 'sometimes';

        return [
            'label' => ['nullable', 'string', 'max:32'],
            'full_name' => [$required, 'string', 'max:120'],
            'phone' => [$required, 'string', 'max:20', fn (string $attribute, mixed $value, Closure $fail) => Phones::parse((string) $value) === null ? $fail(__('Số điện thoại không hợp lệ.')) : null],
            'province_code' => [$required, 'string', 'max:8'],
            // Có danh mục địa giới: tên lấy theo mã (không bắt buộc gửi); không có: AddressBook bắt buộc tên.
            'province_name' => ['sometimes', 'nullable', 'string', 'max:64'],
            'ward_code' => [$required, 'string', 'max:8'],
            'ward_name' => ['sometimes', 'nullable', 'string', 'max:64'],
            'street_line' => [$required, 'string', 'max:255'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}
