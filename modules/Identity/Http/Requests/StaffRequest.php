<?php

declare(strict_types=1);

namespace Modules\Identity\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Identity\Application\ScopeOptions;
use Modules\Identity\Domain\ScopeType;
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
            'assignments.*.scope_type' => ['required', Rule::enum(ScopeType::class)],
            'assignments.*.scope_id' => ['nullable', 'integer', function (string $attribute, mixed $value, Closure $fail): void {
                $type = ScopeType::tryFrom((string) $this->input(str_replace('.scope_id', '.scope_type', $attribute)));
                if ($type !== null && ! app(ScopeOptions::class)->exists($type, $value === null ? null : (int) $value)) {
                    $fail(__('validation.exists', ['attribute' => 'phạm vi']));
                }
            }],
        ];
    }

    /**
     * @return list<array{role_id: int, scope_type: string, scope_id: int|null}>
     */
    public function assignments(): array
    {
        return array_values(array_map(fn (array $assignment): array => [
            'role_id' => (int) $assignment['role_id'],
            'scope_type' => (string) $assignment['scope_type'],
            'scope_id' => isset($assignment['scope_id']) ? (int) $assignment['scope_id'] : null,
        ], (array) $this->validated('assignments')));
    }
}
