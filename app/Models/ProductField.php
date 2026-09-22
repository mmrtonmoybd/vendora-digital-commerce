<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FieldType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ProductField extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFieldFactory> */
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'required' => 'boolean',
            'type' => FieldType::class,
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<Product, self> */
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
