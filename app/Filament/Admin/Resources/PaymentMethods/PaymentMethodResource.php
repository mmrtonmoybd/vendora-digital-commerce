<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PaymentMethods;

use App\Enums\PaymentMethodType;
use App\Filament\Admin\Resources\PaymentMethods\Pages\CreatePaymentMethod;
use App\Filament\Admin\Resources\PaymentMethods\Pages\EditPaymentMethod;
use App\Filament\Admin\Resources\PaymentMethods\Pages\ListPaymentMethods;
use App\Filament\Support\MoneyColumn;
use App\Filament\Support\MoneyInput;
use App\Filament\Support\Presets;
use App\Models\PaymentMethod;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\TernaryFilter;

final class PaymentMethodResource extends Resource
{
    protected static ?string $model = PaymentMethod::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static string|null|UnitEnum $navigationGroup = 'Payments';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Method')->columns(2)->schema([
                TextInput::make('name')->required()->maxLength(80),
                TextInput::make('key')
                    ->required()
                    ->maxLength(40)
                    ->unique(PaymentMethod::class, 'key', ignoreRecord: true)
                    ->helperText('Internal identifier. Changing it on a live shop orphans past orders.'),
                Select::make('type')
                    ->options(PaymentMethodType::class)
                    ->required()
                    ->live()
                    ->native(false)
                    ->helperText('Wallet pays from balance, manual needs a receipt, gateway redirects to shurjoPay.'),
                Select::make('driver')
                    ->options(['shurjopay' => 'shurjoPay'])
                    ->visible(fn (Get $get): bool => $get('type') === PaymentMethodType::Gateway->value)
                    ->native(false),
                Select::make('chip_color')->label('Chip colour')->options(Presets::chipColors())->required()->native(false),
                TextInput::make('short')->label('Chip letters')->required()->maxLength(4),
            ]),

            Section::make('Customer instructions')
                ->visible(fn (Get $get): bool => $get('type') === PaymentMethodType::Manual->value)
                ->columns(2)
                ->schema([
                    TextInput::make('account_number')->label('Number to send money to')->maxLength(60),
                    TextInput::make('account_type')->label('Account type')->placeholder('Personal / Agent / Merchant')->maxLength(40),
                    Textarea::make('instructions')->rows(3)->columnSpanFull(),
                ]),

            Section::make('Limits and fees')->columns(2)->schema([
                MoneyInput::make('min_amount', 'Minimum amount'),
                MoneyInput::make('max_amount', 'Maximum amount')->helperText('Leave empty for no ceiling.'),
                TextInput::make('fee_bps')
                    ->label('Fee (basis points)')
                    ->numeric()->default(0)->minValue(0)
                    ->helperText('100 bps = 1%. Kept in basis points so no float touches a fee.'),
                MoneyInput::make('fee_fixed', 'Flat fee'),
            ]),

            Section::make('Availability')->columns(2)->schema([
                Toggle::make('available_for_orders')->default(true),
                Toggle::make('available_for_deposits')->default(true),
                Toggle::make('is_active')->default(true),
                TextInput::make('sort_order')->numeric()->default(0)->required(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')->searchable()->weight('semibold'),
                TextColumn::make('type')->badge(),
                TextColumn::make('account_number')->label('Number')->copyable()->placeholder('—'),
                MoneyColumn::make('min_amount')->label('Min'),
                TextColumn::make('fee_bps')->label('Fee')->formatStateUsing(fn (int $state): string => number_format($state / 100, 2).'%'),
                IconColumn::make('available_for_orders')->label('Orders')->boolean(),
                IconColumn::make('available_for_deposits')->label('Deposits')->boolean(),
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
            'index' => ListPaymentMethods::route('/'),
            'create' => CreatePaymentMethod::route('/create'),
            'edit' => EditPaymentMethod::route('/{record}/edit'),
        ];
    }
}
