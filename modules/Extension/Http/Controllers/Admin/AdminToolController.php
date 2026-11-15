<?php

declare(strict_types=1);

namespace Modules\Extension\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Modules\Extension\Application\Admin\AdminExtensions;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Shared\Domain\BusinessRuleViolation;
use Throwable;

/**
 * Thực thi công cụ tải file do plugin khai báo ở đầu danh sách Admin (`adminTool`, EP v3 X1): Core kiểm quyền, loại và
 * dung lượng file, ghi audit; plugin chỉ xử lý nội dung. Lỗi không lường trước của plugin → thông báo chung, không lộ lỗi.
 */
final class AdminToolController
{
    public function __invoke(Request $request, string $resource, string $plugin, string $key, AdminExtensions $extensions, AuditLogger $audit): RedirectResponse
    {
        $tool = $extensions->findTool($resource, $plugin, $key) ?? abort(404);
        Gate::authorize($tool['permission']);
        $extensionsList = implode(',', array_map(fn (string $ext): string => ltrim(trim($ext), '.'), explode(',', (string) $tool['accept'])));
        $fieldRules = [];
        foreach ($tool['fields'] as $field) {
            $fieldRules[(string) $field['key']] = ['nullable', 'string', 'max:255'];
        }
        $data = $request->validate(['file' => ['required', 'file', 'max:'.(int) $tool['maxKb'], 'extensions:'.$extensionsList], ...$fieldRules]);
        $fields = array_map('strval', array_filter(array_diff_key($data, ['file' => true]), fn ($value): bool => $value !== null));

        try {
            $message = ($tool['handle'])($request->file('file'), $fields);
        } catch (ValidationException|BusinessRuleViolation $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            Log::warning('Công cụ Admin của plugin lỗi.', ['plugin' => $plugin, 'tool' => $key, 'resource' => $resource]);

            return back()->withErrors(['business' => __('extension::messages.action_failed', ['action' => $tool['label']])]);
        }
        $audit->record('plugin.tool', $resource, null, ['plugin' => $plugin, 'tool' => $key]);

        return back()->with('success', is_string($message) && $message !== '' ? $message : __('extension::messages.action_done', ['action' => $tool['label']]));
    }
}
