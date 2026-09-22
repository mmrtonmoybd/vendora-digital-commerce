<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StockLogReason;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class ProductStockLog extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'reason' => StockLogReason::class,
            'change' => 'integer',
            'stock_after' => 'integer',
        ];
    }

    /** @return BelongsTo<Product, self> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<Admin, self> */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    /** @return MorphTo<Model, self> */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
