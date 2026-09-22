<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\Tables;

use App\Enums\UserStatus;
use App\Enums\WalletTxType;
use App\Filament\Support\MoneyColumn;
use App\Filament\Support\MoneyInput;
use App\Models\User;
use App\Services\WalletService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Password;

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
