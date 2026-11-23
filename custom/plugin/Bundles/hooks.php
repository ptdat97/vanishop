<?php

use Plugin\Bundles\Infrastructure\BundlePageSurface;

return [
    BundlePageSurface::BEFORE_LIST => [
        'type' => 'slot',
        'builder' => true,
        'visibility' => 'public',
        'since' => '1.0.0',
        'args' => [],
        'description' => 'Trước danh sách trên trang Combo.',
    ],
    BundlePageSurface::AFTER_LIST => [
        'type' => 'slot',
        'builder' => true,
        'visibility' => 'public',
        'since' => '1.0.0',
        'args' => [],
        'description' => 'Sau danh sách trên trang Combo.',
    ],
];
