<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
 | Tests run against MySQL, not SQLite. This system's money guarantees rest on
 | SELECT … FOR UPDATE, which SQLite ignores; a concurrency test that passes there would
 | prove nothing about production.
 */
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)->in('Unit');
