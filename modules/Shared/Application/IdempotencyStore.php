<?php

declare(strict_types=1);

namespace Modules\Shared\Application;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * ADR-014. Dùng:
 *   $replay = $store->claim($scope, $key, $hash);   // ngoài transaction nghiệp vụ
 *   if ($replay) return $replay;
 *   try { DB::transaction(function () { …; $store->complete($scope, $key, $response); }); }
 *   catch (Throwable $e) { $store->release($scope, $key); throw $e; }
 *
 * `complete` chạy TRONG transaction nghiệp vụ → kết quả và phản hồi lưu nguyên tử.
 */
final class IdempotencyStore
{
    /** Bản ghi "processing" quá thời gian này coi như tiến trình trước đã chết → cho chạy lại. */
    private const STALE_SECONDS = 300;

    public function claim(string $scope, string $key, string $requestHash, int $ttlHours = 24): ?StoredResponse
    {
        try {
            DB::table('idempotency_keys')->insert([
                'scope' => $scope, 'key' => $key, 'request_hash' => $requestHash, 'status' => 'processing',
                'expires_at' => now()->addHours($ttlHours), 'created_at' => now(), 'updated_at' => now(),
            ]);

            return null;
        } catch (UniqueConstraintViolationException) {
            // Đã có: xử lý bên dưới.
        }

        $row = DB::table('idempotency_keys')->where('scope', $scope)->where('key', $key)->first();
        if ($row === null) {
            return $this->claim($scope, $key, $requestHash, $ttlHours);
        }

        if (! hash_equals((string) $row->request_hash, $requestHash)) {
            throw IdempotencyConflict::differentRequest();
        }

        if ($row->status === 'completed') {
            return new StoredResponse((int) $row->response_status, (array) json_decode((string) $row->response_body, true));
        }

        $taken = DB::table('idempotency_keys')->where('id', $row->id)->where('status', 'processing')
            ->where('updated_at', '<', now()->subSeconds(self::STALE_SECONDS))
            ->update(['updated_at' => now()]);
        if ($taken === 0) {
            throw IdempotencyConflict::inProgress();
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $body
     */
    public function complete(string $scope, string $key, int $status, array $body): void
    {
        DB::table('idempotency_keys')->where('scope', $scope)->where('key', $key)->update([
            'status' => 'completed', 'response_status' => $status, 'response_body' => json_encode($body, JSON_UNESCAPED_UNICODE), 'updated_at' => now(),
        ]);
    }

    /**
     * Nghiệp vụ thất bại → xoá để client thử lại được với cùng key.
     */
    public function release(string $scope, string $key): void
    {
        DB::table('idempotency_keys')->where('scope', $scope)->where('key', $key)->where('status', 'processing')->delete();
    }

    public function pruneExpired(): int
    {
        return DB::table('idempotency_keys')->where('expires_at', '<', now())->delete();
    }
}
