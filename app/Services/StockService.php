<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\StockLogReason;
use App\Exceptions\DomainException;
use App\Models\Admin;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Stock movement, always logged.
 *
 * Reserving is the interesting case: two customers racing for the last licence key must
 * not both succeed, so the product row is locked and the remaining quantity re-read inside
 * the transaction rather than trusted from the request.
 */
final class StockService
{
    /** @throws DomainException when the requested quantity is no longer there */
    public function reserve(Product $product, int $quantity, ?Model $reference = null): Product
    {
        return DB::transaction(function () use ($product, $quantity, $reference): Product {
            /** @var Product $locked */
            $locked = Product::whereKey($product->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->is_active) {
                throw new DomainException("{$locked->name} is no longer available.");
            }

            if ($locked->stock < $quantity) {
                throw new DomainException("{$locked->name} only has {$locked->stock} left in stock.");
            }

            $after = $locked->stock - $quantity;

            $locked->forceFill([
                'stock' => $after,
                'total_sold' => $locked->total_sold + $quantity,
            ])->save();

            $this->log($locked, -$quantity, StockLogReason::Order, $after, $reference);

            return $locked;
        }, 3);
    }

    /** Positive change puts stock back, negative takes it away. */
    public function adjust(Product $product, int $change, StockLogReason $reason, ?Model $reference = null, ?Admin $admin = null, ?string $note = null): Product
    {
        return DB::transaction(function () use ($product, $change, $reason, $reference, $admin, $note): Product {
            /** @var Product $locked */
            $locked = Product::whereKey($product->getKey())->lockForUpdate()->firstOrFail();

            $after = max(0, $locked->stock + $change);
            $locked->forceFill(['stock' => $after])->save();

            $this->log($locked, $change, $reason, $after, $reference, $admin, $note);

            return $locked;
        }, 3);
    }

    /** Set an absolute figure, e.g. after a stock take. */
    public function setTo(Product $product, int $stock, Admin $admin, ?string $note = null): Product
    {
        return $this->adjust($product, $stock - $product->stock, StockLogReason::Manual, null, $admin, $note);
    }

    private function log(Product $product, int $change, StockLogReason $reason, int $after, ?Model $reference, ?Admin $admin = null, ?string $note = null): void
    {
        $product->stockLogs()->create([
            'change' => $change,
            'reason' => $reason,
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
            'admin_id' => $admin?->getKey(),
            'stock_after' => $after,
            'note' => $note,
        ]);
    }
}
