<?php

declare(strict_types=1);

namespace Modules\Shared\Testing;

use Closure;
use Modules\Shared\Contracts\AddressDirectory;

/**
 * Contract test cho AddressDirectory. Kiểm tra: mã; danh sách tỉnh không rỗng, {code, name} chuỗi, mã không trùng; tra
 * tỉnh/phường khớp danh sách; phường sai tỉnh hoặc mã lạ → null. Dữ liệu tĩnh — không I/O mạng.
 */
final class AddressDirectoryContract
{
    /**
     * @param  Closure(): AddressDirectory  $directory
     */
    public static function define(string $label, Closure $directory): void
    {
        describe("AddressDirectory contract: {$label}", function () use ($directory): void {
            it('có mã; tỉnh {code, name}, không trùng mã', function () use ($directory): void {
                expect($directory()->code())->toMatch('/^[a-z][a-z0-9_-]*$/');
                $provinces = $directory()->provinces();
                expect($provinces)->not->toBe([]);
                foreach ($provinces as $province) {
                    expect($province['code'])->toBeString()->and($province['code'])->not->toBe('')
                        ->and($province['name'])->toBeString()->and($province['name'])->not->toBe('');
                }
                expect(count(array_unique(array_column($provinces, 'code'))))->toBe(count($provinces));
            });

            it('tra tỉnh/phường khớp danh sách; sai tỉnh, mã lạ → null', function () use ($directory): void {
                [$first, $second] = array_slice($directory()->provinces(), 0, 2) + [1 => null];
                expect($directory()->province($first['code']))->toBe($first)
                    ->and($directory()->province('ZZZ-KHONG-CO'))->toBeNull();
                $wards = $directory()->wards($first['code']);
                expect($wards)->not->toBe([])
                    ->and($directory()->ward($first['code'], $wards[0]['code']))->toBe($wards[0])
                    ->and($directory()->ward($first['code'], 'ZZZ-KHONG-CO'))->toBeNull();
                if ($second !== null) {
                    expect($directory()->ward($second['code'], $wards[0]['code']))->toBeNull();
                }
                expect($directory()->wards('ZZZ-KHONG-CO'))->toBe([]);
            });
        });
    }
}
