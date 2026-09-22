<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\Tables;

use App\Enums\DepositStatus;
use App\Enums\UserStatus;
use App\Enums\WalletTxType;
use App\Filament\Support\MoneyColumn;
use App\Filament\Support\MoneyInput;
use App\Models\User;
use App\Models\Deposit;
use App\Models\PaymentMethod;
use App\Services\DepositService;
use App\Services\WalletService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Password;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\TrashedFilter;

final class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->paginationPageOptions([10, 25, 50, 100])
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->weight('semibold')
                    ->description(fn (User $record): string => $record->email),
                TextColumn::make('phone')->searchable()->copyable()->toggleable()->placeholder('—'),
                MoneyColumn::make('balance')->label('Wallet'),
                TextColumn::make('orders_count')->counts('orders')->label('Orders')->badge()->sortable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('referral_code')->label('Referral')->copyable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('signupSource.name')->label('Source')->badge()->color('info')->toggleable()->placeholder('Direct'),
                TextColumn::make('created_at')->label('Joined')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(UserStatus::class),
                Filter::make('has_balance')
                    ->label('Has wallet balance')
                    ->query(fn (Builder $query): Builder => $query->where('balance', '>', 0)),
                Filter::make('never_ordered')
                    ->label('Never ordered')
                    ->query(fn (Builder $query): Builder => $query->whereDoesntHave('orders')),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('from')->label('Joined from')->native(false),
                        DatePicker::make('until')->label('Joined until')->native(false),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, string $date): Builder => $q->whereDate('created_at', '<=', $date)))
                    ->indicateUsing(fn (array $data): array => array_values(array_filter([
                        ($data['from'] ?? null) ? 'Joined from '.$data['from'] : null,
                        ($data['until'] ?? null) ? 'Joined until '.$data['until'] : null,
                    ]))),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    Action::make('adjustWallet')
                        ->label('Adjust wallet')
                        ->icon('heroicon-o-wallet')
                        ->color('warning')
                        ->schema([
                            Radio::make('direction')
                                ->options(['credit' => 'Add money', 'debit' => 'Take money back'])
                                ->default('credit')
                                ->required()
                                ->inline(),
                            MoneyInput::make('amount')->required(),
                            Textarea::make('reason')
                                ->required()
                                ->rows(2)
                                ->helperText('Recorded on the ledger entry and visible to the customer.'),
                        ])
                        ->requiresConfirmation()
                        // Goes through WalletService, so it is locked, idempotent and
                        // written to the ledger like every other movement.
                        ->action(function (User $record, array $data): void {
                            $wallet = app(WalletService::class);
                            $amount = (int) $data['amount'];
                            $admin = auth('admin')->user();

                            try {
                                $data['direction'] === 'credit'
                                    ? $wallet->credit($record, $amount, WalletTxType::AdminCredit, null, null, $data['reason'], $admin)
                                    : $wallet->debit($record, $amount, WalletTxType::AdminDebit, null, null, $data['reason'], $admin);
                            } catch (\App\Exceptions\InsufficientBalance $e) {
                                Notification::make()->danger()->title('Not enough balance')->body($e->getMessage())->send();

                                return;
                            }

                            Notification::make()->success()->title('Wallet adjusted')->send();
                        }),
                    /*
                     * Put money in a customer's wallet on their behalf.
                     *
                     * Used when a payment arrives through a channel the customer never
                     * submitted through the form — cash in the office, a bank transfer, a
                     * manual reconciliation. It creates a real deposit record rather than
                     * a bare ledger adjustment, so the customer sees it in their deposit
                     * history with a method and a reference, exactly like any other top-up.
                     */
                    Action::make('recordDeposit')
                        ->label('Record a deposit')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->schema([
                            Select::make('payment_method_id')
                                ->label('Received via')
                                ->options(fn (): array => PaymentMethod::forDeposits()->pluck('name', 'id')->all())
                                ->required()
                                ->native(false),
                            MoneyInput::make('amount')
                                ->required()
                                ->helperText('The amount to add to the wallet. Any method fee is not deducted.'),
                            TextInput::make('sender_number')->label('Sent from')->maxLength(40),
                            TextInput::make('transaction_id')
                                ->label('Reference / TrxID')
                                ->maxLength(190)
                                ->helperText('Leave blank if there is no reference. Duplicates are rejected.'),
                            Textarea::make('reason')
                                ->label('Why are you recording this manually?')
                                ->required()
                                ->rows(2),
                        ])
                        ->requiresConfirmation()
                        ->modalHeading('Record a deposit for this customer')
                        ->action(function (User $record, array $data): void {
                            $method = PaymentMethod::findOrFail($data['payment_method_id']);
                            $admin = auth('admin')->user();
                            $amount = (int) $data['amount'];
                            $reference = $data['transaction_id'] ?: null;

                            if ($reference !== null && Deposit::where('payment_method_id', $method->getKey())
                                ->where('transaction_id', $reference)->exists()) {
                                Notification::make()->danger()
                                    ->title('That reference has already been used')
                                    ->body('Find the existing deposit instead of creating a second one.')
                                    ->send();

                                return;
                            }

                            $deposit = Deposit::create([
                                'deposit_number' => 'DP-'.now()->format('ym').'-'.\Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(6)),
                                'user_id' => $record->getKey(),
                                'payment_method_id' => $method->getKey(),
                                'amount' => $amount,
                                'fee' => 0,
                                'payable' => $amount,
                                'sender_number' => $data['sender_number'] ?: null,
                                'transaction_id' => $reference,
                                'status' => DepositStatus::Pending,
                            ]);

                            // Approving it is what credits the wallet, through the same
                            // locked, idempotent path every other deposit uses.
                            app(DepositService::class)->forceApprove($deposit, $admin, $data['reason']);

                            Notification::make()->success()
                                ->title('Deposit recorded')
                                ->body(\App\Support\Money::format($amount).' added to '.$record->name.'\'s wallet.')
                                ->send();
                        }),

                    Action::make('toggleStatus')
                        ->label(fn (User $record): string => $record->status === UserStatus::Active ? 'Suspend' : 'Activate')
                        ->icon('heroicon-o-no-symbol')
                        ->color(fn (User $record): string => $record->status === UserStatus::Active ? 'danger' : 'success')
                        ->requiresConfirmation()
                        ->action(function (User $record): void {
                            $record->update([
                                'status' => $record->status === UserStatus::Active ? UserStatus::Suspended : UserStatus::Active,
                            ]);

                            Notification::make()->success()->title('Status updated')->send();
                        }),
                    Action::make('verifyEmail')
                        ->label('Mark email verified')
                        ->icon('heroicon-o-check-badge')
                        ->visible(fn (User $record): bool => ! $record->hasVerifiedEmail())
                        ->requiresConfirmation()
                        ->action(function (User $record): void {
                            $record->forceFill(['email_verified_at' => now()])->save();

                            Notification::make()->success()->title('Email marked verified')->send();
                        }),
                    Action::make('sendPasswordReset')
                        ->label('Send password reset')
                        ->icon('heroicon-o-key')
                        ->requiresConfirmation()
                        ->action(function (User $record): void {
                            Password::broker()->sendResetLink(['email' => $record->email]);

                            Notification::make()->success()->title('Reset link sent')->send();
                        }),
                ]),
            ]);
    }
}
