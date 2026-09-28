#!/usr/bin/env bash
# Đo NFR latency của Storefront API (docs/02-architecture/overview.md §7).
#
# Cần server đang chạy và dữ liệu demo:
#   php artisan migrate:fresh --force && php artisan db:seed --class=DemoSeeder
#   php artisan serve --port=8899
#
# Storefront API giới hạn 240 req/phút theo IP. Vì script đo 3 endpoint liên tiếp,
# REQUESTS mặc định = 60 nên tổng 180 < 240. Tăng REQUESTS phải chia nhỏ số lần chạy,
# nếu không sẽ nhận 429 và script báo FAIL.
set -euo pipefail

PORT="${PORT:-8899}"
REQUESTS="${REQUESTS:-60}"
CONCURRENCY="${CONCURRENCY:-5}"
CHANNEL="${CHANNEL:-web-lumiere}"
BASE="http://127.0.0.1:${PORT}"
TARGET_P95_MS="${TARGET_P95_MS:-200}"

command -v ab >/dev/null || { echo "Thiếu ApacheBench (macOS: cài 'ab' vào PATH)"; exit 1; }

# Chặn đo khi endpoint không trả 2xx — nếu không, số liệu sẽ là của lỗi chứ không phải latency thật.
assert_ok() {
    local label="$1" path="$2" code
    code=$(curl -s -o /dev/null -w '%{http_code}' -H "X-Vani-Channel: ${CHANNEL}" "${BASE}${path}")
    if [[ "$code" != "200" ]]; then
        echo "FAIL: ${label} trả HTTP ${code} (không phải 200) — dừng, số liệu không hợp lệ."
        [[ "$code" == "429" ]] && echo "      Gần như chắc chắn đã vượt rate limit 240 req/phút; chờ 1 phút rồi chạy lại, hoặc giảm REQUESTS."
        return 1
    fi
}

measure() {
    local label="$1" path="$2"
    printf '\n== %s ==\n' "$label"
    assert_ok "$label" "$path"

    local out
    out=$(ab -n "$REQUESTS" -c "$CONCURRENCY" -H "X-Vani-Channel: ${CHANNEL}" "${BASE}${path}" 2>&1)
    echo "$out" | grep -E 'Complete requests|Failed requests|Non-2xx|Requests per second|50%|95%|99%'

    local non2xx
    non2xx=$(echo "$out" | awk -F: '/Non-2xx responses/ {gsub(/ /, "", $2); print $2}')
    if [[ -n "$non2xx" && "$non2xx" != "0" ]]; then
        echo "FAIL: ${non2xx}/${REQUESTS} request không phải 2xx — số liệu không hợp lệ."
        return 1
    fi

    local p95
    p95=$(echo "$out" | awk '$1=="95%" {print $2}')
    if [[ -z "$p95" ]]; then
        echo "FAIL: không đọc được P95 từ kết quả ab."
        return 1
    fi
    if (( p95 > TARGET_P95_MS )); then
        echo "FAIL: P95 ${p95}ms vượt ngưỡng ${TARGET_P95_MS}ms"
        return 1
    fi
    echo "PASS: P95 ${p95}ms ≤ ${TARGET_P95_MS}ms"
}

# `set -e` không dừng khi hàm trả về khác 0 nếu lời gọi nằm trong điều kiện;
# dùng || để script thực sự fail (exit != 0) khi có endpoint không đạt.
measure "Danh mục" "/api/storefront/v1/categories" || exit 1
measure "Listing sản phẩm" "/api/storefront/v1/products" || exit 1
measure "PDP" "/api/storefront/v1/products/ao-so-mi-lua" || exit 1

printf '\nLưu ý: php artisan serve là single-process; throughput đo được không đại diện\nnăng lực production. Xem docs/02-architecture/overview.md §7.\n'
