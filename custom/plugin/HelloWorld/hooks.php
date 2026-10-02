<?php

/*
| Hook do plugin vani.hello-world công bố cho plugin khác (ADR-030 §4.H). Plugin dùng hook này khai báo
| "vani.hello-world" trong requires.plugins.
*/

return [
    'vani.hello-world.greeting' => [
        'type' => 'filter',
        'visibility' => 'public',
        'since' => '0.2',
        'args' => ['message' => 'string', 'name' => 'string'],
        'description' => 'Sửa lời chào trả về ở GET /api/storefront/v1/x/vani-hello-world/greeting.',
    ],
];
