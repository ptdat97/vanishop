<?php

declare(strict_types=1);

namespace Modules\Extension\Contracts;

use Modules\Extension\Contracts\Data\FlowStep;

/**
 * Service contract: module Core mô tả các bước của một luồng nghiệp vụ có điểm mở rộng (tính tổng, đặt hàng…), theo
 * đúng thứ tự chạy, để `vani:flows` cho thấy plugin nào chen vào đâu và bước nào nằm trong transaction (học từ
 * "step lists" của Lunar 2.x — ở đây chỉ để xem, thứ tự vẫn do priority của từng điểm mở rộng quyết định).
 */
interface Flows
{
    /**
     * @param  string  $label  khoá dịch
     * @param  callable(): list<FlowStep>  $steps  dựng lúc xem — phản ánh plugin đang bật
     */
    public function describe(string $code, string $label, callable $steps): void;

    /**
     * @return array<string, array{label: string, steps: callable(): list<FlowStep>}> mã luồng => mô tả
     */
    public function all(): array;
}
