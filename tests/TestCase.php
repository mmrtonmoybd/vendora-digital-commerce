<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        /*
         | Do not require a built asset bundle to render a page.
         |
         | Every layout calls @vite, which throws if public/build/manifest.json is absent.
         | That made the whole suite depend on someone having run `npm run build` first —
         | true on a developer's machine by accident, false on a fresh clone and false in
         | CI, where it failed every test that renders a view.
         |
         | The tests here assert behaviour, not the asset pipeline. That the bundle builds
         | is proved by its own CI job, which is the right place for it.
         */
        $this->withoutVite();
    }
}
