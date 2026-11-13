<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Feature test của app, module Core và plugin chạy trên Laravel TestCase với DB được làm mới.
| Unit test của module (Domain thuần) không boot framework.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', '../modules/*/Tests/Feature', '../custom/plugin/*/Tests/Feature');

pest()->extend(TestCase::class)->in('Architecture', 'Concurrency');

// Test trình duyệt thật (pestphp/pest-plugin-browser + Playwright): nhóm `browser`, không chạy mặc định — `vendor/bin/pest --group=browser`.
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->group('browser')
    ->beforeEach(fn () => $this->withVite()) // TestCase tắt Vite cho test HTTP; trình duyệt cần JS đã build (npm run build).
    ->in('Browser');
