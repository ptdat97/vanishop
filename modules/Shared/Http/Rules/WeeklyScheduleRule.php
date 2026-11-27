<?php

declare(strict_types=1);

namespace Modules\Shared\Http\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\Shared\Domain\Schedule\WeeklySchedule;

/** Ô lịch lặp theo tuần của form Admin (0.5.116): null/rỗng = không lặp. */
final class WeeklyScheduleRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value !== null && $value !== [] && ! WeeklySchedule::isValid($value)) {
            $fail(__('shared::texts.weekly_schedule_invalid'));
        }
    }
}
