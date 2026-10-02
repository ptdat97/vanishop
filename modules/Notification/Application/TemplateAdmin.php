<?php

declare(strict_types=1);

namespace Modules\Notification\Application;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Notification\Persistence\Models\NotificationTemplate;

/**
 * CRUD mẫu tin: một mẫu cho mỗi (loại, kênh, locale); optimistic lock; audit.
 */
final class TemplateAdmin
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): NotificationTemplate
    {
        $data['locale'] ??= 'vi';
        $this->assertUnique($data, null);
        $this->assertContent($data);
        $template = NotificationTemplate::query()->create($data);
        $this->audit->record('notification.template.created', 'notification_template', $template->id, $data);

        return $template;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(NotificationTemplate $template, array $data): NotificationTemplate
    {
        return DB::transaction(function () use ($template, $data): NotificationTemplate {
            $locked = NotificationTemplate::query()->whereKey($template->id)->lockForUpdate()->firstOrFail();
            if ((int) $data['lock_version'] !== $locked->lock_version) {
                throw ValidationException::withMessages(['lock_version' => __('notification::messages.stale')]);
            }

            $merged = [...$locked->only(['type', 'channel', 'locale', 'subject', 'body', 'meta', 'active']), ...$data];
            $this->assertUnique($merged, $locked->id);
            $this->assertContent($merged);
            unset($data['lock_version']);
            $locked->fill($data);
            $changes = $locked->getDirty();
            $locked->lock_version++;
            $locked->save();
            $this->audit->record('notification.template.updated', 'notification_template', $locked->id, $changes);

            return $locked;
        });
    }

    public function delete(NotificationTemplate $template): void
    {
        $template->delete();
        $this->audit->record('notification.template.deleted', 'notification_template', $template->id, $template->only(['type', 'channel']));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertUnique(array $data, ?int $exceptId): void
    {
        $exists = NotificationTemplate::query()
            ->where('type', $data['type'])->where('channel', $data['channel'])->where('locale', $data['locale'] ?? 'vi')
            ->when($exceptId !== null, fn ($query) => $query->whereKeyNot($exceptId))
            ->exists();
        if ($exists) {
            throw ValidationException::withMessages(['type' => __('notification::messages.duplicate')]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertContent(array $data): void
    {
        if (($data['body'] ?? '') === '' && ($data['meta'] ?? []) === []) {
            throw ValidationException::withMessages(['body' => __('notification::messages.empty')]);
        }
        if (($data['channel'] ?? '') === 'mail' && ($data['subject'] ?? '') === '') {
            throw ValidationException::withMessages(['subject' => __('notification::messages.subject_required')]);
        }
    }
}
