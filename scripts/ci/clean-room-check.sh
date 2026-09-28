#!/usr/bin/env bash
# Rule R23 (docs/01-principles/architecture-rules.md): mã VaniShop không được chứa dấu vết mã BeikeShop.
set -euo pipefail

paths=(app modules custom config database lang resources routes)
existing=()
for path in "${paths[@]}"; do [[ -e "$path" ]] && existing+=("$path"); done

if grep -rniE 'beike|guangda|hook_filter\(|hook_action\(' "${existing[@]}" --include='*.php' --include='*.vue' --include='*.ts' --include='*.js' --include='*.json' --include='*.css'; then
    echo "::error::Phát hiện chuỗi bị cấm (clean-room). Xem docs/01-principles/clean-room-license.md"
    exit 1
fi

echo "Clean-room check: OK"
