<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FieldType;
use App\Models\Concerns\HasContentTranslations;
use Database\Factories\ProductFieldFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @generated Property annotations, refreshed by scripts/model-properties.php.
 * Larastan cannot read casts declared in a casts() method on a $guarded model, so the
 * enum and date types have to be written down for it — and the IDE benefits too.
 *
 * @property int $id
 * @property int $product_id
 * @property string $key
 * @property string $label
 * @property FieldType $type
 * @property ?array $options
 * @property ?string $placeholder
 * @property bool $required
 * @property int $sort_order
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
final class ProductField extends Model
{
    use HasContentTranslations;

    /** @use HasFactory<ProductFieldFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * Text an administrator typed that a second language needs its own version of.
     *
     * Read through t(), never through the attribute directly — see the trait.
     *
     * @return array<int, string>
     */
    public function translatableFields(): array
    {
        return [
            'label',
            'placeholder',
        ];
    }

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'required' => 'boolean',
            'type' => FieldType::class,
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * The validation rules this field contributes to a checkout request.
     *
     * @return array<int, string>
     */
    public function validationRules(): array
    {
        $rules = [$this->required ? 'required' : 'nullable', $this->type->validationRule()];

        if ($this->type === FieldType::Select && $this->options) {
            $rules[] = 'in:'.implode(',', $this->options);
        }

        if (in_array($this->type, [FieldType::Text, FieldType::Email, FieldType::Url], true)) {
            $rules[] = 'max:255';
        }

        if ($this->type === FieldType::Textarea) {
            $rules[] = 'max:2000';
        }

        return $rules;
    }
}
