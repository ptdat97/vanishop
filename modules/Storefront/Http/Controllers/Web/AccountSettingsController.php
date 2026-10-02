<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Web;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Customer\Contracts\CustomerAccounts;
use Modules\Customer\Contracts\CustomerSessions;
use Modules\Customer\Contracts\Data\CustomerData;
use Modules\Storefront\Application\AddressOptions;

/**
 * Tài khoản khách trên native storefront: sửa hồ sơ, đặt/đổi mật khẩu, sổ địa chỉ (thêm/sửa/xoá/mặc định).
 * Form chạy không cần JS; tỉnh → phường: JS tải qua Storefront API, không JS có nút "Tải danh sách phường/xã".
 */
final class AccountSettingsController
{
    private const TOKEN = 'vani.customer_token';

    public function __construct(
        private readonly CustomerSessions $sessions,
        private readonly CustomerAccounts $accounts,
        private readonly AddressOptions $addressOptions,
    ) {}

    public function profile(Request $request): View
    {
        return view('theme::pages.account.profile', ['customer' => $this->required($request)]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $customer = $this->required($request);
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:190'],
            'birth_date' => ['nullable', 'date', 'before:today', 'after:1900-01-01'],
            'gender' => ['nullable', Rule::in(['female', 'male', 'other'])],
        ]);
        $this->accounts->updateProfile($customer->id, $data);

        return redirect()->route('storefront.account.profile')->with('status', __('storefront::messages.profile_saved'));
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $customer = $this->required($request);
        $data = $request->validate([
            'current_password' => [$customer->hasPassword ? 'required' : 'nullable', 'string', 'max:200'],
            'password' => ['required', 'string', 'min:8', 'max:200', 'confirmed'],
        ]);
        $this->accounts->setPassword($customer->id, $data['current_password'] ?? null, $data['password']);

        return redirect()->route('storefront.account.profile')->with('status', __('storefront::messages.password_saved'));
    }

    public function createAddress(Request $request): View
    {
        $this->required($request);

        return $this->addressForm($request, null);
    }

    public function storeAddress(Request $request): RedirectResponse
    {
        $customer = $this->required($request);
        if ($request->input('action') === 'reload') {
            return back()->withInput();
        }
        $this->accounts->addAddress($customer->id, $this->validatedAddress($request));

        return redirect()->route('storefront.account.addresses')->with('status', __('storefront::messages.address_saved'));
    }

    public function editAddress(Request $request, int $address): View
    {
        $customer = $this->required($request);
        $current = $this->accounts->address($customer->id, $address) ?? abort(404);

        return $this->addressForm($request, $current);
    }

    public function updateAddress(Request $request, int $address): RedirectResponse
    {
        $customer = $this->required($request);
        if ($request->input('action') === 'reload') {
            return back()->withInput();
        }
        $this->accounts->updateAddress($customer->id, $address, $this->validatedAddress($request));

        return redirect()->route('storefront.account.addresses')->with('status', __('storefront::messages.address_saved'));
    }

    public function defaultAddress(Request $request, int $address): RedirectResponse
    {
        $customer = $this->required($request);
        $this->accounts->updateAddress($customer->id, $address, ['is_default' => true]);

        return redirect()->route('storefront.account.addresses')->with('status', __('storefront::messages.address_saved'));
    }

    public function destroyAddress(Request $request, int $address): RedirectResponse
    {
        $customer = $this->required($request);
        $this->accounts->deleteAddress($customer->id, $address);

        return redirect()->route('storefront.account.addresses')->with('status', __('storefront::messages.address_deleted'));
    }

    /**
     * @param  array<string, mixed>|null  $address
     */
    private function addressForm(Request $request, ?array $address): View
    {
        $directory = $this->addressOptions->directory();
        $province = (string) $request->old('province_code', $address['province_code'] ?? '');

        return view('theme::pages.account.address-form', [
            'address' => $address,
            'provinces' => $directory?->provinces(),
            'wards' => $directory === null || $province === '' ? [] : $directory->wards($province),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedAddress(Request $request): array
    {
        $hasDirectory = $this->addressOptions->directory() !== null;
        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:32'],
            'full_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:20'], // định dạng: Customer kiểm tra (customer.phone_invalid)
            'province_code' => [$hasDirectory ? 'required' : 'nullable', 'string', 'max:8'],
            'province_name' => [$hasDirectory ? 'nullable' : 'required', 'string', 'max:64'],
            'ward_code' => [$hasDirectory ? 'required' : 'nullable', 'string', 'max:8'],
            'ward_name' => [$hasDirectory ? 'nullable' : 'required', 'string', 'max:64'],
            'street_line' => ['required', 'string', 'max:255'],
            'is_default' => ['sometimes', 'boolean'],
        ]);

        return [...$data, 'province_code' => $data['province_code'] ?? '', 'ward_code' => $data['ward_code'] ?? '', 'is_default' => $request->boolean('is_default')];
    }

    private function required(Request $request): CustomerData
    {
        $token = (string) $request->session()->get(self::TOKEN, '');

        return ($token === '' ? null : $this->sessions->authenticate($token)) ?? abort(401);
    }
}
