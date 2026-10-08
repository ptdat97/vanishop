#!/usr/bin/env bash
# Đo nhanh TTFB trang công khai (page cache nóng) + Storefront API bằng ApacheBench — chạy được trên dev (Herd, PHP-FPM)
# hoặc staging. Không thay load test k6 (scripts/load/storefront.js): ab chỉ đo latency với vài luồng song song.
#
#   BASE=http://vanishop.test ./scripts/load/smoke-pages.sh        # exit 0 = đạt, 1 = không đạt
#
# Trang HTML không có rate limit; API giới hạn 240 req/phút/IP trừ khi IP nằm trong VANI_LOAD_TEST_IPS.
set -euo pipefail

BASE="${BASE:-http://vanishop.test}"
REQUESTS="${REQUESTS:-200}"
API_REQUESTS="${API_REQUESTS:-60}"
CONCURRENCY="${CONCURRENCY:-10}"
command -v ab >/dev/null || { echo "Thiếu ApacheBench (ab)"; exit 1; }

slug=$(curl -s "${BASE}/api/storefront/v1/products?per_page=1" | php -r 'echo json_decode(stream_get_contents(STDIN), true)["data"][0]["slug"] ?? "";')
category=$(curl -s "${BASE}/api/storefront/v1/categories" | php -r 'echo json_decode(stream_get_contents(STDIN), true)["data"][0]["slug"] ?? "";')
[[ -n "$slug" ]] || { echo "FAIL: không lấy được sản phẩm từ ${BASE}"; exit 1; }

status=0
measure() {
    local label="$1" url="$2" target="$3" requests="$4"
    curl -s -o /dev/null "$url" # làm nóng page cache
    local out p50 p95 non2xx rps
    out=$(ab -q -n "$requests" -c "$CONCURRENCY" -H 'Accept: text/html,application/json' "$url" 2>&1)
    p50=$(echo "$out" | awk '$1=="50%" {print $2}')
    p95=$(echo "$out" | awk '$1=="95%" {print $2}')
    rps=$(echo "$out" | awk '/Requests per second/ {print $4}')
    non2xx=$(echo "$out" | awk -F: '/Non-2xx responses/ {gsub(/ /, "", $2); print $2}')
    local verdict="PASS"
    if [[ -n "$non2xx" && "$non2xx" != "0" ]] || [[ -z "$p95" ]] || (( p95 > target )); then
        verdict="FAIL"; status=1
    fi
    printf '%-28s P50 %4sms  P95 %4sms (≤%sms)  %6s req/s  non-2xx %s  %s\n' "$label" "$p50" "$p95" "$target" "$rps" "${non2xx:-0}" "$verdict"
}

measure "Trang chủ" "${BASE}/" 300 "$REQUESTS"
[[ -n "$category" ]] && measure "Danh mục" "${BASE}/danh-muc/${category}" 300 "$REQUESTS"
measure "PDP" "${BASE}/san-pham/${slug}" 300 "$REQUESTS"
measure "API listing" "${BASE}/api/storefront/v1/products?per_page=24" 200 "$API_REQUESTS"
measure "API PDP" "${BASE}/api/storefront/v1/products/${slug}" 200 "$API_REQUESTS"
exit $status
