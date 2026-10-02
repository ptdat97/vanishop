<?php

declare(strict_types=1);

namespace Modules\Extension\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Modules\Extension\Application\Admin\AdminExtensions;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Shared\Domain\BusinessRuleViolation;
use Throwable;

/**
 * Thực thi thao tác do plugin khai báo trên màn hình Admin của Core (`adminAction`, ADR-030 §4.A):
 * Core kiểm tra quyền + ghi audit, plugin chỉ cung cấp `handle(id)`. Thao tác hàng loạt: lỗi một bản ghi không
 * chặn bản ghi khác, báo số thành công/thất bại.
 */
final class AdminActionController
{
    public function __invoke(Request $request, string $resource, string $plugin, string $key, AdminExtensions $extensions, AuditLogger $audit): RedirectResponse
    {
        $action = $extensions->findAction($resource, $plugin, $key) ?? abort(404);
        Gate::authorize($action['permission']);
        $ids = array_values(array_unique(array_map('intval', (array) $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['integer', 'min:1'],
        ])['ids'])));

        $messages = [];
        $failed = 0;
        foreach ($ids as $id) {
            try {
                $message = ($action['handle'])($id);
                $audit->record('plugin.action', $resource, $id, ['plugin' => $plugin, 'action' => $key]);
                if (is_string($message) && $message !== '') {
                    $messages[] = $message;
                }
            } catch (BusinessRuleViolation $exception) {
                if (count($ids) === 1) {
                    throw $exception;
                }
                $failed++;
            } catch (Throwable $exception) {
                report($exception);
                Log::warning('Thao tác Admin của plugin lỗi.', ['plugin' => $plugin, 'action' => $key, 'resource' => $resource, 'id' => $id]);
                if (count($ids) === 1) {
                    return back()->withErrors(['business' => __('extension::messages.action_failed', ['action' => $action['label']])]);
                }
                $failed++;
            }
        }

        $summary = count($ids) === 1
            ? ($messages[0] ?? __('extension::messages.action_done', ['action' => $action['label']]))
            : __('extension::messages.bulk_done', ['action' => $action['label'], 'done' => count($ids) - $failed, 'failed' => $failed]);

        return back()->with('success', $summary);
    }
}
