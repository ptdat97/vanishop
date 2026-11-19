<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Web;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Customer\Contracts\CustomerAccounts;
use Modules\Customer\Contracts\CustomerRejected;
use Modules\Customer\Contracts\CustomerSessions;
use Modules\Customer\Contracts\Data\CustomerData;
use Modules\Customer\Contracts\Requests\AddressRequest;
use Modules\Customer\Contracts\Requests\DeleteAccountRequest;
use Modules\Customer\Contracts\Requests\PasswordRequest;
use Modules\Customer\Contracts\Requests\ProfileRequest;
use Modules\Shared\Support\LocalizedUrls;
use Modules\Storefront\Application\AddressOptions;

/**
 * Tài khoản khách trên native storefront: sửa hồ sơ, đặt/đổi mật khẩu, sổ địa chỉ (thêm/sửa/xoá/mặc định), xoá
 * tài khoản (OTP tới SĐT của tài khoản; còn đơn/đổi trả đang xử lý thì chưa xoá được).
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

    public function updateProfile(ProfileRequest $request): RedirectResponse
    {
        $customer = $this->required($request);
        $updated = $this->accounts->updateProfile($customer->id, $request->validated());
        // Đổi/thêm email → gửi link xác minh tới email mới (L40); quá số lần gửi thì vẫn lưu hồ sơ.
        $status = __('storefront::messages.profile_saved');
        if ($updated->email !== null && $updated->email !== $customer->email) {
            try {
                $this->sessions->sendEmailVerification($customer->id);
                $status .= ' '.__('customer::messages.email_verification_sent', ['email' => $updated->email]);
            } catch (CustomerRejected) {
                // Đã gửi quá nhiều link tới địa chỉ này — khách bấm "Gửi lại" sau.
            }
        }

        return redirect()->to(LocalizedUrls::route('storefront.account.profile'))->with('status', $status);
    }

    public function resendEmailVerification(Request $request): RedirectResponse
    {
        $customer = $this->required($request);
        if (! $this->sessions->sendEmailVerification($customer->id)) {
            throw CustomerRejected::emailAlreadyVerified();
        }

        return redirect()->to(LocalizedUrls::route('storefront.account.profile'))->with('status', __('customer::messages.email_verification_sent', ['email' => $customer->email]));
    }

    /** Link trong email xác minh (không cần đăng nhập — token đủ). */
    public function verifyEmail(string $token): RedirectResponse
    {
        $customer = $this->sessions->verifyEmail($token);
        $target = LocalizedUrls::route('storefront.account.profile');

        return $customer === null
            ? redirect()->to($target)->withErrors(['business' => __('customer::messages.email_link_invalid')])
            : redirect()->to($target)->with('status', __('customer::messages.email_verified'));
    }

    public function updatePassword(PasswordRequest $request): RedirectResponse
    {
        $customer = $this->required($request);
        $data = $request->validated();
        $this->accounts->setPassword($customer->id, $data['current_password'] ?? null, $data['password']);

        return redirect()->to(LocalizedUrls::route('storefront.account.profile'))->with('status', __('storefront::messages.password_saved'));
    }

    public function deletion(Request $request): View
    {
        $customer = $this->required($request);

        return view('theme::pages.account.delete', [
            'customer' => $customer,
            'blockers' => $this->accounts->deletionBlockers($customer->id),
        ]);
    }

    /**
     * Xác nhận bằng mật khẩu hiện tại; tài khoản chưa có mật khẩu (đăng nhập OTP/mạng xã hội) đặt mật khẩu ở trang hồ sơ trước.
     */
    public function destroyAccount(DeleteAccountRequest $request): RedirectResponse
    {
        $customer = $this->required($request);
        $data = $request->validated();
        $this->accounts->deleteAccount($customer->id, $data['password']);

        // Ẩn danh hoá đã thu hồi mọi token; bỏ phiên web để không còn dấu vết đăng nhập.
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->to(LocalizedUrls::route('storefront.home'))->with('status', __('storefront::messages.account_deleted'));
    }

    public function createAddress(Request $request): View
    {
        $this->required($request);

        return $this->addressForm($request, null);
    }

    public function storeAddress(AddressRequest $request): RedirectResponse
    {
        $customer = $this->required($request);
        if ($request->input('action') === 'reload') {
            return back()->withInput();
        }
        $this->accounts->addAddress($customer->id, $request->toAddressData());

        return redirect()->to(LocalizedUrls::route('storefront.account.addresses'))->with('status', __('storefront::messages.address_saved'));
    }

    public function editAddress(Request $request, int $address): View
    {
        $customer = $this->required($request);
        $current = $this->accounts->address($customer->id, $address) ?? abort(404);

        return $this->addressForm($request, $current);
    }

    public function updateAddress(AddressRequest $request, int $address): RedirectResponse
    {
        $customer = $this->required($request);
        if ($request->input('action') === 'reload') {
            return back()->withInput();
        }
        $this->accounts->updateAddress($customer->id, $address, $request->toAddressData());

        return redirect()->to(LocalizedUrls::route('storefront.account.addresses'))->with('status', __('storefront::messages.address_saved'));
    }

    public function defaultAddress(Request $request, int $address): RedirectResponse
    {
        $customer = $this->required($request);
        $this->accounts->updateAddress($customer->id, $address, ['is_default' => true]);

        return redirect()->to(LocalizedUrls::route('storefront.account.addresses'))->with('status', __('storefront::messages.address_saved'));
    }

    public function destroyAddress(Request $request, int $address): RedirectResponse
    {
        $customer = $this->required($request);
        $this->accounts->deleteAddress($customer->id, $address);

        return redirect()->to(LocalizedUrls::route('storefront.account.addresses'))->with('status', __('storefront::messages.address_deleted'));
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

    private function required(Request $request): CustomerData
    {
        $token = (string) $request->session()->get(self::TOKEN, '');

        return ($token === '' ? null : $this->sessions->authenticate($token)) ?? abort(401);
    }
}
