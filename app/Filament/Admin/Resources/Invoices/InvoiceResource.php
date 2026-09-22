<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Invoices;

use App\Enums\InvoiceStatus;
use App\Exceptions\DomainException;
use App\Filament\Admin\Resources\Invoices\Pages\CreateInvoice;
use App\Filament\Admin\Resources\Invoices\Pages\ListInvoices;
use App\Filament\Support\MoneyColumn;
use App\Filament\Support\MoneyInput;
use App\Models\CustomInvoice;
use App\Services\InvoiceService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/** A one-off bill for work that is not in the catalogue. */
final class InvoiceResource extends Resource
{
    protected static ?string $model = CustomInvoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCurrencyDollar;

    protected static string|null|UnitEnum $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Custom invoices';

    protected static ?string $recordTitleAttribute = 'invoice_number';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Invoice')->columns(2)->schema([
                Select::make('user_id')
                    ->label('Customer')
                    ->relationship('user', 'name')
                    ->searchable(['name', 'email'])
                    ->preload()
                    ->required(),
                TextInput::make('product_name')->label('What is this for?')->required()->maxLength(190),
                MoneyInput::make('amount')->required(),
                DatePicker::make('due_at')->label('Due')->native(false),
                Textarea::make('description')->rows(2)->columnSpanFull(),
                Textarea::make('note')->label('Note for the customer')->rows(2)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('invoice_number')->label('Invoice')->searchable()->copyable()->weight('semibold'),
                TextColumn::make('user.name')->label('Customer')->searchable(),
                TextColumn::make('product_name')->label('For')->wrap()->limit(40),
                MoneyColumn::make('amount'),
                TextColumn::make('status')->badge(),
                TextColumn::make('due_at')->date()->placeholder('—')
                    ->color(fn (CustomInvoice $r): string => $r->isOverdue() ? 'danger' : 'gray'),
                TextColumn::make('order.order_number')->label('Order')->placeholder('—')->toggleable(),
            ])
            ->filters([SelectFilter::make('status')->options(InvoiceStatus::class)->multiple()])
            ->recordActions([
                ActionGroup::make([
                    Action::make('markPaid')
                        ->label('Mark as paid')
                        ->icon('heroicon-o-banknotes')
                        ->color('warning')
                        ->visible(fn (CustomInvoice $record): bool => $record->isPayable())
                        ->schema([
                            Textarea::make('reason')
                                ->label('How was this paid?')
                                ->required()
                                ->rows(2)
                                ->placeholder('e.g. bank transfer received outside the shop'),
                        ])
                        ->requiresConfirmation()
                        ->modalDescription('This settles the invoice and opens a fulfilment order, without taking money.')
                        ->action(function (CustomInvoice $record, array $data): void {
                            try {
                                app(InvoiceService::class)->markPaidByAdmin($record, auth('admin')->user(), $data['reason']);
                                Notification::make()->success()->title('Invoice settled')->send();
                            } catch (DomainException $e) {
                                Notification::make()->danger()->title('That is not possible')->body($e->getMessage())->send();
                            }
                        }),
                    Action::make('cancel')
                        ->label('Cancel')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn (CustomInvoice $record): bool => $record->isPayable())
                        ->schema([Textarea::make('reason')->rows(2)])
                        ->action(function (CustomInvoice $record, array $data): void {
                            try {
                                app(InvoiceService::class)->cancel($record, auth('admin')->user(), $data['reason'] ?? null);
                                Notification::make()->success()->title('Invoice cancelled')->send();
                            } catch (DomainException $e) {
                                Notification::make()->danger()->title('That is not possible')->body($e->getMessage())->send();
                            }
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvoices::route('/'),
            'create' => CreateInvoice::route('/create'),
        ];
    }
}
