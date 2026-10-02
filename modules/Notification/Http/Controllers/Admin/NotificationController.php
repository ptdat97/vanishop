<?php

declare(strict_types=1);

namespace Modules\Notification\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Identity\Contracts\Data\ScopeRef;
use Modules\Notification\Application\ChannelRegistry;
use Modules\Notification\Application\TemplateAdmin;
use Modules\Notification\Contracts\Data\NotificationType;
use Modules\Notification\Contracts\NotificationCatalog;
use Modules\Notification\Persistence\Models\NotificationLog;
use Modules\Notification\Persistence\Models\NotificationTemplate;

/**
 * Mẫu tin & nhật ký gửi — cấp Owner.
 */
final class NotificationController
{
    public function templates(ChannelRegistry $channels, NotificationCatalog $catalog): Response
    {
        Gate::authorize('notifications.view', [ScopeRef::owner()]);

        return Inertia::render('Notification::Templates/Index', [
            'baseUrl' => route('admin.notifications.templates.index'),
            'types' => array_map(fn (NotificationType $type): array => ['label' => $type->label, 'variables' => $type->variables], $catalog->types()),
            'channels' => array_keys($channels->all()),
            'templates' => NotificationTemplate::query()->orderBy('type')->orderBy('channel')->orderBy('locale')->get()
                ->map(fn (NotificationTemplate $template): array => $this->present($template))->all(),
            'can' => ['manage' => Gate::allows('notifications.manage', [ScopeRef::owner()])],
        ]);
    }

    public function store(Request $request, TemplateAdmin $admin): RedirectResponse
    {
        Gate::authorize('notifications.manage', [ScopeRef::owner()]);
        $admin->create($request->validate($this->rules(creating: true)));

        return back()->with('success', __('notification::messages.saved'));
    }

    public function update(Request $request, NotificationTemplate $template, TemplateAdmin $admin): RedirectResponse
    {
        Gate::authorize('notifications.manage', [ScopeRef::owner()]);
        $admin->update($template, $request->validate([...$this->rules(creating: false), 'lock_version' => ['required', 'integer']]));

        return back()->with('success', __('notification::messages.saved'));
    }

    public function destroy(NotificationTemplate $template, TemplateAdmin $admin): RedirectResponse
    {
        Gate::authorize('notifications.manage', [ScopeRef::owner()]);
        $admin->delete($template);

        return back()->with('success', __('notification::messages.deleted'));
    }

    public function logs(Request $request): Response
    {
        Gate::authorize('notifications.view', [ScopeRef::owner()]);
        $status = is_string($request->query('status')) ? $request->query('status') : null;
        $q = is_string($request->query('q')) ? trim($request->query('q')) : '';
        $page = NotificationLog::query()
            ->when($status !== null && $status !== '', fn ($query) => $query->where('status', $status))
            ->when($q !== '', fn ($query) => $query->where(fn ($query) => $query->where('recipient', 'like', "%{$q}%")->orWhere('idempotency_key', 'like', "%{$q}%")))
            ->orderByDesc('id')->paginate(50)->withQueryString();

        return Inertia::render('Notification::Logs/Index', [
            'baseUrl' => route('admin.notifications.logs'),
            'filters' => ['status' => $status, 'q' => $q],
            'statuses' => [NotificationLog::QUEUED, NotificationLog::SENT, NotificationLog::FAILED, NotificationLog::SKIPPED],
            'logs' => collect($page->items())->map(fn (NotificationLog $log): array => [
                'id' => $log->id, 'type' => $log->type, 'channel' => $log->channel, 'recipient' => $this->mask($log->recipient),
                'status' => $log->status, 'attempts' => $log->attempts, 'error' => $log->error, 'subject' => $log->subject,
                'created_at' => $log->created_at->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i'),
                'sent_at' => $log->sent_at?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i'),
            ])->all(),
            'pagination' => ['page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
        ]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    private function rules(bool $creating): array
    {
        $required = $creating ? 'required' : 'sometimes';

        return [
            'type' => [$required, 'string', 'max:64', 'regex:/^[a-z0-9_]+$/'],
            'channel' => [$required, 'string', 'max:32', 'regex:/^[a-z0-9_]+$/'],
            'locale' => ['nullable', Rule::in(['vi', 'en'])],
            'subject' => ['nullable', 'string', 'max:190'],
            'body' => ['nullable', 'string', 'max:5000'],
            'meta' => ['nullable', 'array'],
            'active' => ['boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function present(NotificationTemplate $template): array
    {
        return $template->only(['id', 'type', 'channel', 'locale', 'subject', 'body', 'meta', 'active', 'lock_version']);
    }

    private function mask(string $recipient): string
    {
        if (str_contains($recipient, '@')) {
            [$name, $domain] = explode('@', $recipient, 2);

            return mb_substr($name, 0, 2).'***@'.$domain;
        }

        return mb_strlen($recipient) > 6 ? mb_substr($recipient, 0, 5).'****'.mb_substr($recipient, -2) : '****';
    }
}
