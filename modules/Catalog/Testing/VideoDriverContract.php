<?php

declare(strict_types=1);

namespace Modules\Catalog\Testing;

use Closure;
use Modules\Catalog\Contracts\VideoDriver;

/**
 * Contract test cho VideoDriver. Kiểm tra: mã, nhãn, gợi ý nhập; nhận đường dẫn mẫu → mã tham chiếu ổn định (nhận lại
 * chính mã đó); chuỗi rác → null; link xem/nhúng là https. Không gọi mạng.
 */
final class VideoDriverContract
{
    /**
     * @param  Closure(): VideoDriver  $driver
     */
    public static function define(string $label, Closure $driver, string $sampleInput): void
    {
        describe("VideoDriver contract: {$label}", function () use ($driver, $sampleInput): void {
            it('có mã, nhãn, gợi ý nhập', function () use ($driver): void {
                expect($driver()->code())->toMatch('/^[a-z][a-z0-9_-]*$/')
                    ->and($driver()->label())->not->toBe('')
                    ->and($driver()->inputHint())->not->toBe('');
            });

            it('nhận đường dẫn mẫu → mã tham chiếu ổn định; chuỗi rác → null; link https', function () use ($driver, $sampleInput): void {
                $reference = $driver()->reference($sampleInput);
                expect($reference)->toBeString();
                expect($reference)->not->toBe('')
                    ->and($driver()->reference($sampleInput))->toBe($reference)
                    ->and($driver()->reference('khong phai video'))->toBeNull()
                    ->and($driver()->watchUrl($reference))->toStartWith('https://');
                $embed = $driver()->embedUrl($reference);
                expect($embed === null || str_starts_with($embed, 'https://'))->toBeTrue();
            });
        });
    }
}
