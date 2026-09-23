<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\StockLogReason;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @generated Property annotations, refreshed by scripts/model-properties.php.
 * Larastan cannot read casts declared in a casts() method on a $guarded model, so the
 * enum and date types have to be written down for it — and the IDE benefits too.
 *
 * @property int $id
 * @property int $product_id
 * @property int $change
 * @property StockLogReason $reason
 * @property ?string $reference_type
 * @property ?int $reference_id
 * @property ?int $admin_id
 * @property int $stock_after
 * @property ?string $note
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
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

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<Admin, $this> */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    /** @return MorphTo<Model, $this> */
    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
