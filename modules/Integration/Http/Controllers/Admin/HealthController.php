<?php

declare(strict_types=1);

namespace Modules\Integration\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Identity\Contracts\Data\ScopeRef;
use Modules\Integration\Application\HealthQueries;
use Modules\Integration\Application\ReplayService;
use Modules\Integration\Domain\MessageStatus;
use Modules\Integration\Persistence\Models\WebhookSubscription;

/**
 * Integration Health — cấp Owner: backlog, message lỗi (payload đã che PII), replay, client & webhook.
 */
final class HealthController
{
    public function index(Request $request, HealthQueries $queries): Response
    {
        Gate::authorize('integration.view', [ScopeRef::owner()]);
        $box = $request->query('box') === 'inbox' ? 'inbox' : 'outbox';
        $status = is_string($request->query('status')) ? $request->query('status') : null;
        $target = is_string($request->query('target')) ? $request->query('target') : null;

        return Inertia::render('Integration::Health/Index', [
            'baseUrl' => route('admin.integration.health'),
            'filters' => ['box' => $box, 'status' => $status, 'target' => $target],
            'statuses' => array_column(MessageStatus::cases(), 'value'),
            'summary' => $queries->summary(),
            'messages' => $queries->messages($box, $status, $target),
            'clients' => $queries->clients(),
            'can' => [
                'replay' => Gate::allows('integration.replay', [ScopeRef::owner()]),
                'manage' => Gate::allows('integration.manage', [ScopeRef::owner()]),
            ],
        ]);
    }

    public function replay(Request $request, ReplayService $replay): RedirectResponse
    {
        Gate::authorize('integration.replay', [ScopeRef::owner()]);
        $data = $request->validate([
            'box' => ['required', Rule::in(['outbox', 'inbox'])],
            'ids' => ['nullable', 'array', 'max:'.ReplayService::MAX_PER_CALL],
            'ids.*' => ['integer'],
            'status' => ['nullable', Rule::in([MessageStatus::Failed->value, MessageStatus::Dead->value])],
            'target' => ['nullable', 'string', 'max:96'],
        ]);

        $filter = array_filter([
            'ids' => isset($data['ids']) ? array_map('intval', $data['ids']) : null,
            'status' => $data['status'] ?? null,
            'target' => $data['target'] ?? null,
        ], fn (mixed $value): bool => $value !== null);

        $count = $data['box'] === 'inbox' ? $replay->replayInbox($filter) : $replay->replayOutbox($filter);

        return back()->with('success', __('integration::messages.replayed', ['count' => $count]));
    }

    public function resumeSubscription(WebhookSubscription $subscription, AuditLogger $audit): RedirectResponse
    {
        Gate::authorize('integration.manage', [ScopeRef::owner()]);
        $subscription->update(['status' => 'active', 'failing_since' => null]);
        $audit->record('integration.subscription.resumed', 'integration_webhook_subscription', $subscription->id);

        return back()->with('success', __('integration::messages.resumed'));
    }
}
