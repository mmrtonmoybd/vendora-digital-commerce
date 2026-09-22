<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Coupons;

use App\Enums\CouponType;
use App\Filament\Admin\Resources\Coupons\Pages\CreateCoupon;
use App\Filament\Admin\Resources\Coupons\Pages\EditCoupon;
use App\Filament\Admin\Resources\Coupons\Pages\ListCoupons;
use App\Filament\Support\MoneyColumn;
use App\Filament\Support\MoneyInput;
use App\Models\Coupon;
use App\Support\Money;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

final class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

    protected static string|null|UnitEnum $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'code';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Coupon')->columns(2)->schema([
                TextInput::make('code')
                    ->required()
                    ->maxLength(40)
                    ->unique(Coupon::class, 'code', ignoreRecord: true)
                    ->helperText('Stored uppercase; customers may type it either way.'),
                TextInput::make('label')
                    ->maxLength(120)
                    ->helperText('The confirmation the customer sees, e.g. "10% off applied!".'),
                Select::make('type')->options(CouponType::class)->required()->live()->native(false),
                TextInput::make('value')
                    ->numeric()
                    ->required()
                    ->suffix(fn (Get $get): string => $get('type') === CouponType::Percent->value ? '%' : Money::symbol())
                    ->helperText(fn (Get $get): string => $get('type') === CouponType::Percent->value
                        ? 'A whole percentage, 1 to 100.'
                        : 'Amount in taka.')
                    // A percentage is stored as-is; a fixed amount is paisa like every
                    // other money column.
                    ->formatStateUsing(fn (?int $state, Get $get): ?string => $state === null
                        ? null
                        : ($get('type') === CouponType::Percent->value ? (string) $state : Money::toDecimal($state)))
                    ->dehydrateStateUsing(fn (?string $state, Get $get): int => $get('type') === CouponType::Percent->value
                        ? (int) $state
                        : Money::fromDecimal($state)),
            ]),

            Section::make('Rules')->columns(2)->schema([
                MoneyInput::make('min_order', 'Minimum order')->helperText('Below this, the coupon is refused.'),
                MoneyInput::make('max_discount', 'Maximum discount')->helperText('Caps a percentage coupon. Leave empty for no cap.'),
                TextInput::make('usage_limit')->numeric()->minValue(1)->helperText('Total redemptions allowed. Empty means unlimited.'),
                TextInput::make('per_user_limit')->numeric()->minValue(1)->helperText('Redemptions per customer. Empty means unlimited.'),
                DateTimePicker::make('starts_at')->seconds(false),
                DateTimePicker::make('expires_at')->seconds(false),
                Select::make('products')->relationship('products', 'name')->multiple()->searchable()->preload()
                    ->helperText('Limit to these products. Leave empty to apply to everything.'),
                Select::make('categories')->relationship('categories', 'name')->multiple()->searchable()->preload()
                    ->helperText('Limit to these categories.'),
                Toggle::make('is_active')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('code')->searchable()->weight('semibold')->copyable(),
                TextColumn::make('type')->badge(),
                TextColumn::make('value')
                    ->label('Discount')
                    ->formatStateUsing(fn (int $state, Coupon $record): string => $record->type === CouponType::Percent
                        ? $state.'%'
                        : Money::format($state)),
                MoneyColumn::make('min_order')->label('Min order'),
                TextColumn::make('used_count')->label('Used')->badge()
                    ->formatStateUsing(fn (int $state, Coupon $record): string => $record->usage_limit
                        ? "{$state} / {$record->usage_limit}"
                        : (string) $state),
                TextColumn::make('expires_at')->dateTime()->placeholder('No expiry')->sortable(),
                IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Active'),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('from')->label('Created from')->native(false),
                        DatePicker::make('until')->label('Created until')->native(false),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('created_at', '<=', $date)))
                    ->indicateUsing(fn (array $data): array => array_values(array_filter([
                        ($data['from'] ?? null) ? 'Created from '.$data['from'] : null,
                        ($data['until'] ?? null) ? 'Created until '.$data['until'] : null,
                    ]))),
            ])
            ->recordActions([ActionGroup::make([EditAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCoupons::route('/'),
            'create' => CreateCoupon::route('/create'),
            'edit' => EditCoupon::route('/{record}/edit'),
        ];
    }

    /** @return array<int, string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['code'];
    }
}
