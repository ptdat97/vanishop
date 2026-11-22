<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

interface DemoContent
{
    public const TAG = 'vani.demo.content';

    public function seed(): void;

    /** @return array<string, array{type: string, value: string}> */
    public function links(): array;
}
