<?php

declare(strict_types=1);

namespace Plugin\HelloWorld\Http\Controllers;

use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class HelloController
{
    public function __invoke(): Response
    {
        Gate::authorize('hello-world.view');

        return Inertia::render('HelloWorld::Index', ['message' => 'Xin chào từ plugin!']);
    }
}
