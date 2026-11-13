<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Persistence\Models\Style;

/*
| Hợp đồng lỗi của /api/* (docs/06-api/api.md §2, ApiErrorRenderer): {"error": {code, message, details?, correlation_id}};
| mã ổn định, thông điệp theo locale, không lộ tên class/câu mặc định tiếng Anh, giữ header của lỗi HTTP.
*/

beforeEach(function () {
    config(['app.debug' => false]);
    Route::prefix('api/__contract')->group(function () {
        Route::get('model', fn () => Style::query()->findOrFail(999_999));
        Route::get('abort', fn () => abort(404, 'Không có đơn này.'));
        Route::get('forbidden', fn () => throw new AuthorizationException);
        Route::get('throttle', fn () => throw new ThrottleRequestsException('Too Many Attempts.', null, ['Retry-After' => 30]));
        Route::get('auth', fn () => throw new AuthenticationException);
        Route::get('validation', fn () => throw ValidationException::withMessages(['email' => 'Email không hợp lệ.']));
        Route::get('crash', fn () => throw new RuntimeException('SQLSTATE secret detail'));
    });
});

it('lỗi HTTP chung: mã http.<status>, câu chung theo locale, không lộ tên class hay câu tiếng Anh của framework', function (string $path, int $status, string $message) {
    $response = $this->getJson("/api/__contract/{$path}")->assertStatus($status)
        ->assertJsonPath('error.code', "http.{$status}")
        ->assertJsonPath('error.message', $message)
        ->assertJsonStructure(['error' => ['code', 'message', 'correlation_id']]);

    expect($response->getContent())->not->toContain('Modules\\\\')->not->toContain('No query results');
})->with([
    'model không tồn tại' => ['model', 404, 'Không tìm thấy.'],
    'không có quyền' => ['forbidden', 403, 'Không có quyền thực hiện thao tác này.'],
    'quá tần suất' => ['throttle', 429, 'Quá nhiều yêu cầu, vui lòng thử lại sau.'],
]);

it('thông điệp ứng dụng tự đặt khi abort được giữ; header Retry-After (429) và Allow (405) được giữ', function () {
    $this->getJson('/api/__contract/abort')->assertNotFound()->assertJsonPath('error.message', 'Không có đơn này.');
    $this->getJson('/api/__contract/throttle')->assertStatus(429)->assertHeader('Retry-After', '30');
    $this->postJson('/api/__contract/abort')->assertStatus(405)->assertJsonPath('error.code', 'http.405')->assertHeader('Allow');
});

it('chưa đăng nhập, dữ liệu không hợp lệ, lỗi máy chủ: mã riêng, lỗi máy chủ không lộ chi tiết', function () {
    $this->getJson('/api/__contract/auth')->assertUnauthorized()->assertJsonPath('error.code', 'auth.unauthenticated')->assertJsonPath('error.message', 'Cần đăng nhập.');
    $this->getJson('/api/__contract/validation')->assertUnprocessable()
        ->assertJsonPath('error.code', 'validation.failed')
        ->assertJsonPath('error.details', [['field' => 'email', 'messages' => ['Email không hợp lệ.']]]);

    $crash = $this->getJson('/api/__contract/crash')->assertServerError()->assertJsonPath('error.code', 'server.error');
    expect($crash->getContent())->not->toContain('SQLSTATE');
});
