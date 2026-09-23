<?php

declare(strict_types=1);
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Every model that claims a factory must actually have a working one.
 *
 * Fourteen models carried an `@use HasFactory<XFactory>` docblock for a factory that was
 * never written. The annotation is a promise to anyone writing a test, and calling
 * Model::factory() on one of them died. This walks the model directory rather than a list,
 * so a model added later is covered without anyone remembering.
 */
it('can build every model that declares a factory', function (): void {
    $failures = [];

    foreach (glob(app_path('Models/*.php')) as $file) {
        $model = 'App\\Models\\'.basename($file, '.php');

        if (! class_exists($model) || ! method_exists($model, 'factory')) {
            continue;
        }

        if (! in_array(HasFactory::class, class_uses_recursive($model), true)) {
            continue;
        }

        try {
            $record = $model::factory()->create();

            expect($record->exists)->toBeTrue();
        } catch (Throwable $e) {
            $failures[] = class_basename($model).': '.$e->getMessage();
        }
    }

    expect($failures)->toBe([]);
});
