<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Observability\HealthCheck;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /health — trạng thái tổng hợp cho load balancer/giám sát. Chi tiết từng kiểm tra chỉ trả khi có header
 * X-Health-Token khớp VANI_HEALTH_TOKEN (không lộ thông tin vận hành ra ngoài).
 */
final class HealthController
{
    public function __invoke(Request $request, HealthCheck $health): JsonResponse
    {
        $result = $health->run();
        $token = (string) config('vanishop.health.token', '');
        $detailed = $token !== '' && hash_equals($token, (string) $request->header('X-Health-Token', ''));

        return response()->json($detailed ? $result : ['status' => $result['status']], $result['status'] === 'fail' ? 503 : 200)
            ->header('Cache-Control', 'no-store');
    }
}
