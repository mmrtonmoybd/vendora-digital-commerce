<?php

declare(strict_types=1);

namespace App\Filament\Customer\Resources\Invoices;

use App\Enums\InvoiceStatus;
use App\Exceptions\DomainException;
use App\Filament\Customer\Concerns\BelongsToCustomer;
use App\Filament\Customer\Resources\Invoices\Pages\ListInvoices;
use App\Models\CustomInvoice;
use App\Services\InvoiceService;
use App\Support\Money;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

final class InvoiceResource extends Resource
{
    use BelongsToCustomer;

    protected static ?string $model = CustomInvoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|null|UnitEnum $navigationGroup = 'Orders';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Invoices';

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('invoice_number')->label('Invoice')->copyable()->weight('semibold'),
                TextColumn::make('product_name')->label('For')->wrap(),
                TextColumn::make('amount')->formatStateUsing(fn (int $state): string => Money::format($state))->alignEnd(),
                TextColumn::make('status')->badge(),
                TextColumn::make('due_at')
                    ->label('Due')
                    ->date()
                    ->placeholder('No due date')
                    ->color(fn (CustomInvoice $r): string => $r->isOverdue() ? 'danger' : 'gray'),
                TextColumn::make('note')->wrap()->limit(60)->placeholder('—')->toggleable(),
            ])
            ->filters([SelectFilter::make('status')->options(InvoiceStatus::class)])
            ->recordActions([
                ActionGroup::make([
                    Action::make('payWithWallet')
                        ->label('Pay from wallet')
                        ->icon('heroicon-o-wallet')
                        ->color('success')
                        ->visible(fn (CustomInvoice $record): bool => $record->isPayable())
                        ->requiresConfirmation()
                        ->modalDescription(fn (CustomInvoice $record): string => 'This takes '.Money::format($record->amount).' from your wallet balance.')
                        ->action(function (CustomInvoice $record): void {
                            try {
                                app(InvoiceService::class)->payWithWallet($record);
                                Notification::make()->success()->title('Invoice paid')->send();
                            } catch (DomainException|\App\Exceptions\InsufficientBalance $e) {
                                Notification::make()->danger()->title('Could not pay')->body($e->getMessage())->send();
                            }
                        }),
                    Action::make('payOnline')
                        ->label('Pay by card')
                        ->icon('heroicon-o-credit-card')
                        ->visible(fn (CustomInvoice $record): bool => $record->isPayable())
                        ->url(fn (CustomInvoice $record): string => route('payments.shurjopay.start', [
                            'type' => 'invoice',
                            'id' => $record->getKey(),
                        ])),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListInvoices::route('/')];
    }
}
