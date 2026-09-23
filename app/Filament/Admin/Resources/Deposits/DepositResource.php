<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Deposits;

use App\Enums\DepositStatus;
use App\Exceptions\DomainException;
use App\Filament\Admin\Resources\Deposits\Pages\ListDeposits;
use App\Filament\Support\MoneyColumn;
use App\Models\Deposit;
use App\Services\DepositService;
use App\Support\Money;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\URL;
use UnitEnum;

/**
 * Wallet top-ups waiting on a human.
 *
 * The duplicate-TrxID warning is the important column here: the same receipt being
 * submitted twice is the most common way a shop like this loses money.
 */
final class DepositResource extends Resource
{
    protected static ?string $model = Deposit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static string|null|UnitEnum $navigationGroup = 'Payments';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'deposit_number';

    public static function getNavigationBadge(): ?string
    {
        $count = Deposit::where('status', DepositStatus::Pending->value)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->paginationPageOptions([10, 25, 50, 100])
            ->columns([
                TextColumn::make('deposit_number')->label('Deposit')->searchable()->copyable()->weight('semibold'),
                TextColumn::make('user.name')->label('Customer')->searchable()
                    ->description(fn (Deposit $record): string => $record->user?->email ?? ''),
                MoneyColumn::make('amount'),
                TextColumn::make('paymentMethod.name')->label('Method')->badge()->color('gray'),
                TextColumn::make('sender_number')->label('From')->copyable()->placeholder('—'),
                TextColumn::make('transaction_id')
                    ->label('TrxID')
                    ->copyable()
                    ->placeholder('—')
                    ->badge()
                    // A repeated transaction ID is the signal staff must not miss.
                    ->color(fn (Deposit $record): string => $record->hasDuplicateTransactionId() ? 'danger' : 'gray')
                    ->tooltip(fn (Deposit $record): ?string => $record->hasDuplicateTransactionId()
                        ? 'This transaction ID has already been used.'
                        : null),
                TextColumn::make('status')->badge(),
                TextColumn::make('created_at')->label('Submitted')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(DepositStatus::class)->multiple(),
                SelectFilter::make('payment_method')->relationship('paymentMethod', 'name')->preload(),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('from')->label('Submitted from')->native(false),
                        DatePicker::make('until')->label('Submitted until')->native(false),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('created_at', '<=', $date)))
                    ->indicateUsing(fn (array $data): array => array_values(array_filter([
                        ($data['from'] ?? null) ? 'Submitted from '.$data['from'] : null,
                        ($data['until'] ?? null) ? 'Submitted until '.$data['until'] : null,
                    ]))),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('viewScreenshot')
                        ->label('View screenshot')
                        ->icon('heroicon-o-photo')
                        ->visible(fn (Deposit $record): bool => filled($record->screenshot))
                        ->url(fn (Deposit $record): string => URL::temporarySignedRoute(
                            'files.show',
                            now()->addMinutes(15),
                            ['path' => $record->screenshot],
                        ), shouldOpenInNewTab: true),

                    Action::make('approve')
                        ->label('Approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (Deposit $record): bool => $record->status->isOpen())
                        ->schema([Textarea::make('note')->label('Internal note')->rows(2)])
                        ->requiresConfirmation()
                        ->modalDescription(fn (Deposit $record): string => 'This credits '.Money::format($record->amount).' to the wallet.')
                        ->action(function (Deposit $record, array $data): void {
                            try {
                                app(DepositService::class)->approve($record, auth('admin')->user(), $data['note'] ?? null);
                                Notification::make()->success()->title('Deposit approved')->send();
                            } catch (DomainException $e) {
                                Notification::make()->danger()->title('That is not possible')->body($e->getMessage())->send();
                            }
                        }),

                    /*
                     * Credit a top-up the gateway never confirmed.
                     *
                     * The everyday case is a timeout after the customer was charged: the
                     * bank shows the money, our verification call does not.
                     */
                    Action::make('forceApprove')
                        ->label('Approve manually')
                        ->icon('heroicon-o-banknotes')
                        ->color('warning')
                        ->visible(fn (Deposit $record): bool => $record->status !== DepositStatus::Completed)
                        ->schema([
                            Textarea::make('reason')
                                ->label('Why are you approving this manually?')
                                ->required()
                                ->rows(3)
                                ->placeholder('e.g. gateway timed out but the statement shows the payment'),
                        ])
                        ->requiresConfirmation()
                        ->action(function (Deposit $record, array $data): void {
                            try {
                                app(DepositService::class)->forceApprove($record, auth('admin')->user(), $data['reason']);
                                Notification::make()->success()->title('Deposit credited')->send();
                            } catch (DomainException $e) {
                                Notification::make()->danger()->title('That is not possible')->body($e->getMessage())->send();
                            }
                        }),

                    Action::make('reject')
                        ->label('Reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn (Deposit $record): bool => $record->status !== DepositStatus::Completed)
                        ->schema([
                            Textarea::make('reason')->required()->rows(2)
                                ->helperText('The customer sees this.'),
                        ])
                        ->action(function (Deposit $record, array $data): void {
                            try {
                                app(DepositService::class)->reject($record, auth('admin')->user(), $data['reason']);
                                Notification::make()->warning()->title('Deposit rejected')->send();
                            } catch (DomainException $e) {
                                Notification::make()->danger()->title('That is not possible')->body($e->getMessage())->send();
                            }
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListDeposits::route('/')];
    }

    /** @return array<int, string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['deposit_number', 'transaction_id', 'sender_number'];
    }

    public static function canCreate(): bool
    {
        // A deposit is a customer telling us they sent money. Inventing one here would
        // credit a wallet with no receipt behind it.
        return false;
    }
}
