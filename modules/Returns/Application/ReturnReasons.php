<?php

declare(strict_types=1);

namespace Modules\Returns\Application;

use Illuminate\Validation\ValidationException;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Returns\Persistence\Models\ReturnReason;
use Modules\Returns\Persistence\Models\ReturnRequest;
use Modules\Shared\Support\StoreLocale;

/**
 * Danh mục lý do đổi trả (reference-parity §3.13-1). Lý do đã dùng trong yêu cầu đổi trả không xoá được — chỉ tắt.
 */
final class ReturnReasons
{
    /** @var array<string, ReturnReason>|null */
    private ?array $all = null;

    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @return array<string, ReturnReason> mã => lý do, theo thứ tự hiển thị (gồm lý do đã tắt)
     */
    public function all(): array
    {
        return $this->all ??= ReturnReason::query()->orderBy('position')->orderBy('id')->get()->keyBy('code')->all();
    }

    /**
     * @return list<array{code: string, label: string, store_fault: bool}>
     */
    public function active(string $locale): array
    {
        return array_values(array_map(
            fn (ReturnReason $reason): array => ['code' => $reason->code, 'label' => $this->name($reason, $locale), 'store_fault' => $reason->store_fault],
            array_filter($this->all(), fn (ReturnReason $reason): bool => $reason->is_active),
        ));
    }

    public function isActive(string $code): bool
    {
        return ($this->all()[$code] ?? null)?->is_active === true;
    }

    /** Tên theo ngôn ngữ, rơi về ngôn ngữ mặc định rồi mã (lý do đã bị xoá khỏi danh mục vẫn hiện mã). */
    public function label(string $code, string $locale): string
    {
        $reason = $this->all()[$code] ?? null;

        return $reason === null ? $code : $this->name($reason, $locale);
    }

    /**
     * @param  array{code: string, names: array<string, string|null>, position?: int, is_active?: bool, store_fault?: bool}  $data
     */
    public function save(?ReturnReason $reason, array $data): ReturnReason
    {
        $names = array_filter(array_map(fn (?string $name): ?string => $name === null || trim($name) === '' ? null : trim($name), $data['names']));
        if (! isset($names[StoreLocale::default()])) {
            throw ValidationException::withMessages(['names.'.StoreLocale::default() => __('returns::messages.reason_name_required')]);
        }
        if ($reason !== null && $reason->code !== $data['code'] && $this->used($reason)) {
            throw ValidationException::withMessages(['code' => __('returns::messages.reason_code_locked')]);
        }

        $reason ??= new ReturnReason;
        $reason->fill([
            'code' => $data['code'], 'names' => $names, 'position' => (int) ($data['position'] ?? 0),
            'is_active' => (bool) ($data['is_active'] ?? true), 'store_fault' => (bool) ($data['store_fault'] ?? false),
        ])->save();
        $this->all = null;
        $this->audit->record('return.reason_saved', 'return_reason', $reason->id, ['code' => $reason->code, 'is_active' => $reason->is_active]);

        return $reason;
    }

    public function delete(ReturnReason $reason): void
    {
        if ($this->used($reason)) {
            throw ValidationException::withMessages(['reason' => __('returns::messages.reason_in_use')]);
        }
        $reason->delete();
        $this->all = null;
        $this->audit->record('return.reason_deleted', 'return_reason', $reason->id, ['code' => $reason->code]);
    }

    public function used(ReturnReason $reason): bool
    {
        return ReturnRequest::query()->where('reason_code', $reason->code)->exists();
    }

    private function name(ReturnReason $reason, string $locale): string
    {
        return (string) ($reason->names[$locale] ?? $reason->names[StoreLocale::default()] ?? $reason->code);
    }
}
