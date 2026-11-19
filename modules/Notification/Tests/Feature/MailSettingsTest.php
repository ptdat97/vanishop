<?php

use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Notification\Application\Mail\MailSettings;
use Modules\Tenancy\Contracts\Settings;

/*
| Admin → Cài đặt → Email gửi đi: mailer mặc định `vani` đọc cấu hình lúc gửi — chưa đặt SMTP thì gửi bằng MAIL_MAILER
| (test: array), đặt người gửi thì thay ô "Từ", chọn SMTP + máy chủ thì gửi qua SMTP của cửa hàng; nút "Gửi email thử".
*/

beforeEach(function () {
    $this->settings = app(Settings::class);
    $this->sent = fn (): array => Mail::mailer('array')->getSymfonyTransport()->messages()->all();
    $this->send = fn () => Mail::raw('Nội dung', fn (Message $message) => $message->to('khach@example.com')->subject('Xin chào'));
});

it('chưa cấu hình: gửi bằng mailer của máy chủ, người gửi theo MAIL_FROM_*', function () {
    expect(config('mail.default'))->toBe('vani');
    ($this->send)();

    expect(($this->sent)())->toHaveCount(1)
        ->and(($this->sent)()[0]->getOriginalMessage()->getFrom()[0]->getAddress())->toBe(config('mail.from.address'));
});

it('đặt email + tên người gửi → thay ô "Từ" và người gửi của phong bì; tên trống → tên theo máy chủ', function () {
    $this->settings->set('core', MailSettings::FROM_ADDRESS, 'cskh@vanishop.vn');
    $this->settings->set('core', MailSettings::FROM_NAME, 'Vani Shop');
    ($this->send)();
    $sent = ($this->sent)()[0];

    expect($sent->getOriginalMessage()->getFrom()[0]->toString())->toBe('"Vani Shop" <cskh@vanishop.vn>')
        ->and($sent->getEnvelope()->getSender()->getAddress())->toBe('cskh@vanishop.vn');

    $this->settings->forget('core', MailSettings::FROM_NAME);
    ($this->send)();
    expect(($this->sent)()[1]->getOriginalMessage()->getFrom()[0]->getName())->toBe((string) config('mail.from.name'));
});

it('chọn SMTP nhưng chưa có máy chủ → vẫn gửi bằng mailer của máy chủ', function () {
    $this->settings->set('core', MailSettings::TRANSPORT, MailSettings::SMTP);
    ($this->send)();

    expect(($this->sent)())->toHaveCount(1);
});

describe('Admin', function () {
    beforeEach(function () {
        $this->staff = StaffUser::factory()->withPermissions(['admin.access', 'settings.manage'])->create(['email' => 'chu-shop@example.com']);
        $this->actingAs($this->staff, 'staff');
    });

    it('trang nhóm Email gửi đi có đủ mục và nút "Gửi email thử"; mật khẩu là secret; email người gửi sai bị từ chối', function () {
        $this->get('/admin/settings/system/mail')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('fields', fn ($fields) => collect($fields)->pluck('key')->all() === [MailSettings::TRANSPORT, MailSettings::HOST, MailSettings::PORT, MailSettings::ENCRYPTION, MailSettings::USERNAME, MailSettings::PASSWORD, MailSettings::FROM_ADDRESS, MailSettings::FROM_NAME]
                && collect($fields)->firstWhere('key', MailSettings::PASSWORD)['type'] === 'secret')
            ->where('actions', [['label' => 'Gửi email thử', 'url' => route('admin.notifications.mail.test')]]));

        $this->put('/admin/settings', ['namespace' => 'core', 'values' => [MailSettings::FROM_ADDRESS => 'khong-phai-email']])->assertSessionHasErrors('values.'.MailSettings::FROM_ADDRESS);
        $this->put('/admin/settings', ['namespace' => 'core', 'values' => [MailSettings::PASSWORD => 'mat-khau-ung-dung']])->assertSessionHasNoErrors();
        $this->get('/admin/settings/system/mail')->assertInertia(fn (Assert $page) => $page
            ->where('fields', fn ($fields) => collect($fields)->firstWhere('key', MailSettings::PASSWORD)['value'] === null));
    });

    it('gửi email thử tới email của nhân viên đang đăng nhập', function () {
        $this->post('/admin/notifications/mail/test')->assertRedirect()->assertSessionHas('success', 'Đã gửi email thử tới chu-shop@example.com. Kiểm tra hộp thư (cả mục Spam).');

        expect(($this->sent)())->toHaveCount(1)
            ->and(($this->sent)()[0]->getOriginalMessage()->getTo()[0]->getAddress())->toBe('chu-shop@example.com');
    });

    it('SMTP của cửa hàng không kết nối được → báo lỗi nguyên văn, không rơi sang mailer của máy chủ', function () {
        $this->settings->set('core', MailSettings::TRANSPORT, MailSettings::SMTP);
        $this->settings->set('core', MailSettings::HOST, '127.0.0.1');
        $this->settings->set('core', MailSettings::PORT, 1);
        $this->settings->set('core', MailSettings::ENCRYPTION, 'none');

        $this->post('/admin/notifications/mail/test')->assertRedirect()
            ->assertSessionHas('error', fn (string $message): bool => str_starts_with($message, 'Không gửi được email thử: ') && str_contains($message, '127.0.0.1'));
        expect(($this->sent)())->toBe([]);
    });

    it('không có quyền cấu hình → 403', function () {
        $this->actingAs(StaffUser::factory()->withPermissions(['admin.access'])->create(), 'staff');
        $this->post('/admin/notifications/mail/test')->assertForbidden();
        expect(($this->sent)())->toBe([]);
    });
});
