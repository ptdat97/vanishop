<?php

declare(strict_types=1);

namespace Modules\Identity\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Identity\Application\PermissionRegistry;
use Modules\Identity\Persistence\Models\Role;

final class RoleRequest extends FormRequest
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
        $role = $this->route('role');

        return [
            'code' => [$role instanceof Role ? 'prohibited' : 'required', 'string', 'max:64', 'regex:/^[a-z][a-z0-9_]*$/', Rule::unique('roles', 'code')],
            'name' => ['required', 'string', 'max:120'],
            'permissions' => ['present', 'array'],
            // Không cấp `*` qua giao diện: chỉ vai trò hệ thống (owner_admin) có.
            'permissions.*' => ['string', 'distinct', Rule::in(array_keys(app(PermissionRegistry::class)->all()))],
        ];
    }

    /**
     * @return list<string>
     */
    public function permissions(): array
    {
        return array_values(array_map('strval', (array) $this->validated('permissions')));
    }
}
