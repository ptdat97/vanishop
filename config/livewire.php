<?php

/*
 * Livewire chỉ dùng cho dashboard Pulse (tự khai báo asset). Không tự chèn script vào mọi trang HTML: script mang
 * token CSRF theo phiên, làm hỏng HTML dùng chung của trang storefront cache được (storefront §5). Các khoá khác
 * lấy mặc định của package (mergeConfigFrom).
 */
return [
    'inject_assets' => false,
];
