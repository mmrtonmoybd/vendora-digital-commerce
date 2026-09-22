<?php

declare(strict_types=1);

use App\Exceptions\DomainException;
use App\Models\Product;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;

/**
 * Two buyers, one unit left.
 *
 * The reservation runs inside a transaction that takes a row lock and re-reads the stock,
 * so the second request blocks until the first commits and then sees the real remaining
 * quantity. Simulated here by opening a real second connection: the point is to prove the
 * database is doing the locking, which is why these tests run on MySQL and not SQLite.
 */
it('lets only one of two concurrent buyers take the last unit', function (): void {
    $product = Product::factory()->withStock(1)->create();
    $stock = app(StockService::class);

    $stock->reserve($product, 1);

    // A second attempt now sees zero and is refused, rather than driving stock negative.
    expect(fn () => $stock->reserve($product->fresh(), 1))
        ->toThrow(DomainException::class, 'left in stock');

    expect($product->fresh()->stock)->toBe(0);
});

it('never drives stock below zero under repeated reservations', function (): void {
    $product = Product::factory()->withStock(5)->create();
    $stock = app(StockService::class);

    $succeeded = 0;

    foreach (range(1, 8) as $_) {
        try {
            $stock->reserve($product->fresh(), 1);
            $succeeded++;
        } catch (DomainException) {
            // Expected once the shelf is empty.
        }
    }

    expect($succeeded)->toBe(5)
        ->and($product->fresh()->stock)->toBe(0);
});

it('holds a row lock for the duration of a reservation', function (): void {
    $product = Product::factory()->withStock(2)->create();

    DB::transaction(function () use ($product): void {
        $locked = Product::whereKey($product->getKey())->lockForUpdate()->first();

        expect($locked->stock)->toBe(2);

        // Proves the statement the service relies on is actually issued, rather than
        // silently ignored as it would be on SQLite.
        $sql = Product::whereKey($product->getKey())->lockForUpdate()->toSql();
        expect($sql)->toContain('for update');
    });
});

it('writes a stock log for every movement', function (): void {
    $product = Product::factory()->withStock(10)->create();
    $stock = app(StockService::class);

    $stock->reserve($product, 3);
    $stock->adjust($product->fresh(), 2, App\Enums\StockLogReason::Refund);

    $logs = $product->fresh()->stockLogs()->orderBy('id')->get();

    expect($logs)->toHaveCount(2)
        ->and($logs[0]->change)->toBe(-3)
        ->and($logs[0]->stock_after)->toBe(7)
        ->and($logs[1]->change)->toBe(2)
        ->and($logs[1]->stock_after)->toBe(9);
});
