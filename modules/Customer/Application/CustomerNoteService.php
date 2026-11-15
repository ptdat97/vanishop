<?php

declare(strict_types=1);

namespace Modules\Customer\Application;

use Illuminate\Support\Facades\DB;
use Modules\Customer\Contracts\CustomerNotes;
use Modules\Shared\Context\CurrentContext;
use Modules\Shared\Support\StoreClock;

/**
 * Ghi chú CSKH theo khách (EP v3 X6) — xem CustomerNotes.
 */
final class CustomerNoteService implements CustomerNotes
{
    public function __construct(private readonly CurrentContext $context) {}

    public function add(int $customerId, string $note, string $source): void
    {
        $note = trim($note);
        if ($note === '') {
            return;
        }
        $actor = $this->context->has() ? $this->context->actor() : null;
        DB::table('customer_notes')->insert([
            'customer_id' => $customerId, 'note' => mb_substr($note, 0, 500), 'source' => mb_substr($source, 0, 64),
            'actor_type' => $actor?->type->value, 'actor_id' => $actor?->id, 'created_at' => now(),
        ]);
    }

    public function latest(int $customerId, int $limit = 20): array
    {
        return DB::table('customer_notes')->where('customer_id', $customerId)->orderByDesc('id')->limit($limit)->get(['note', 'source', 'created_at'])
            ->map(fn (object $row): array => ['note' => (string) $row->note, 'source' => (string) $row->source, 'at' => StoreClock::format((string) $row->created_at)])->all();
    }
}
