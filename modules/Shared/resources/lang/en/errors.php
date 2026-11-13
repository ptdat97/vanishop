<?php

return [
    'validation' => 'The given data was invalid.',
    'server' => 'Something went wrong. Please try again later.',
    'http' => [
        400 => 'Bad request.',
        401 => 'Authentication required.',
        403 => 'You are not allowed to perform this action.',
        404 => 'Not found.',
        405 => 'Method not allowed.',
        409 => 'Conflict, please try again.',
        413 => 'The request is too large.',
        415 => 'Unsupported media type.',
        419 => 'Your session has expired. Reload the page and try again.',
        429 => 'Too many requests, please try again later.',
        503 => 'Service unavailable, please come back later.',
    ],
];
