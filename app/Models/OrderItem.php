<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AccessStatus;
use App\Support\Money;
use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @generated Property annotations, refreshed by scripts/model-properties.php.
 * Larastan cannot read casts declared in a casts() method on a $guarded model, so the
 * enum and date types have to be written down for it — and the IDE benefits too.
 *
 * @property int $id
 * @property int $order_id
 * @property ?int $product_id
 * @property array $product_snapshot
 * @property int $unit_price
 * @property int $quantity
 * @property int $line_total
 * @property ?array $field_values
 * @property ?string $delivery_email
 * @property AccessStatus $access_status
 * @property ?Carbon $granted_at
 * @property ?int $granted_by
 * @property ?Carbon $confirmed_at
 * @property ?string $access_reference
 * @property ?string $admin_delivery_note
 * @property ?Carbon $warranty_until
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
final class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'product_snapshot' => 'array',
            'field_values' => 'array',
            'unit_price' => 'integer',
            'quantity' => 'integer',
            'line_total' => 'integer',
            'access_status' => AccessStatus::class,
            'granted_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'warranty_until' => 'datetime',
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<Admin, $this> */
    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'granted_by');
    }

    /** @return HasMany<AccessEvent, $this> */
    public function accessEvents(): HasMany
    {
        return $this->hasMany(AccessEvent::class)->oldest();
    }

    /** @return HasMany<AccessProblemReport, $this> */
    public function problemReports(): HasMany
    {
        return $this->hasMany(AccessProblemReport::class)->latest();
    }

    public function platformName(): ?string
    {
        return $this->product_snapshot['platform_name'] ?? $this->product?->platform?->name;
    }

    public function accessRole(): ?string
    {
        return $this->product_snapshot['access_role'] ?? $this->product?->access_role;
    }

    public function slaMinutes(): int
    {
        return (int) ($this->product_snapshot['fulfillment_sla_minutes'] ?? $this->product?->fulfillment_sla_minutes ?? 30);
    }

    /** Minutes since the order was paid, which is when the fulfilment clock starts. */
    public function minutesWaiting(): int
    {
        $paidAt = $this->order?->paid_at;

        if ($paidAt === null || ! $this->access_status->isOpen()) {
            return 0;
        }

        return (int) $paidAt->diffInMinutes(now());
    }

    public function isBreachingSla(): bool
    {
        return $this->access_status->isOpen() && $this->minutesWaiting() > $this->slaMinutes();
    }

    public function isUnderWarranty(): bool
    {
        return $this->warranty_until !== null && $this->warranty_until->isFuture();
    }

    /** What the customer is told to do once the invitation arrives. */
    public function inviteInstructions(): ?string
    {
        return $this->product?->platform?->invite_instructions;
    }

    public function name(): string
    {
        return (string) ($this->product_snapshot['name'] ?? 'Deleted product');
    }

    public function slug(): ?string
    {
        return $this->product_snapshot['slug'] ?? null;
    }

    public function formattedLineTotal(): string
    {
        return Money::format($this->line_total);
    }
}
