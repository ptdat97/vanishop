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
