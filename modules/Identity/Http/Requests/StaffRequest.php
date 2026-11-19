<?php

declare(strict_types=1);

namespace Modules\Identity\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Identity\Persistence\Models\StaffUser;

final class StaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user('staff')?->can('staff.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $staff = $this->route('staff');

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => [$staff instanceof StaffUser ? 'prohibited' : 'required', 'email', 'max:190', Rule::unique('staff_users', 'email')],
            'assignments' => ['present', 'array', 'max:20'],
            'assignments.*.role_id' => ['required', 'integer', Rule::exists('roles', 'id')],
        ];
    }

    /**
     * @return list<array{role_id: int}>
     */
    public function assignments(): array
    {
        return array_values(array_map(fn (array $assignment): array => ['role_id' => (int) $assignment['role_id']], (array) $this->validated('assignments')));
    }
}
