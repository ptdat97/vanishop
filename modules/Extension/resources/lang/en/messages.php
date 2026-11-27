<?php

return [
    'action_done' => '":action" done.',
    'action_failed' => 'Could not run ":action". Please try again or contact an administrator.',
    'bulk_done' => '":action": :done succeeded, :failed failed.',
    'cache_cleared' => 'Cache cleared. Pages and data are rebuilt on their next view.',
    'cache_shares_redis_database' => 'Cannot clear: the cache store shares a Redis database with sessions/queue. Set a separate REDIS_CACHE_DB (docs/18-operations/production.md).',
    'cache_shares_durable_store' => 'Cannot clear: the default cache store is the durable store (CACHE_DURABLE_STORE) — clearing would delete tokens and import previews.',
];
