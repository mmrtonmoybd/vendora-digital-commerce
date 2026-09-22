<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Withdrawals;

use App\Enums\WithdrawalStatus;
use App\Exceptions\DomainException;
use App\Filament\Admin\Resources\Withdrawals\Pages\ListWithdrawals;
use App\Filament\Support\MoneyColumn;
use App\Models\WithdrawalRequest;
use App\Services\AffiliateService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Affiliate payouts.
 *
 * The money left the wallet when the request was made, so it is already held. Rejecting is
 * what returns it — which is why rejection goes through the service rather than a status
 * update.
 */
final class WithdrawalResource extends Resource
{
    protected static ?string $model = WithdrawalRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static string|null|UnitEnum $navigationGroup = 'Affiliate';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Withdrawals';

    public static function getNavigationBadge(): ?string
    {
        $count = WithdrawalRequest::where('status', WithdrawalStatus::Pending->value)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('request_number')->label('Request')->searchable()->copyable()->weight('semibold'),
                TextColumn::make('user.name')->label('Customer')->searchable()
                    ->description(fn (WithdrawalRequest $r): string => $r->user?->email ?? ''),
                MoneyColumn::make('amount'),
                TextColumn::make('method')->badge()->color('gray'),
                TextColumn::make('account_details')
                    ->label('Pay to')
                    ->copyable()
                    ->wrap()
                    ->state(fn (WithdrawalRequest $r): string => collect($r->account_details ?? [])
                        ->map(fn ($v, $k): string => str($k)->headline().': '.$v)
                        ->implode(' · ')),
                TextColumn::make('status')->badge(),
                TextColumn::make('created_at')->label('Requested')->dateTime()->sortable(),
            ])
            ->filters([SelectFilter::make('status')->options(WithdrawalStatus::class)->multiple()])
            ->recordActions([
                ActionGroup::make([
                    Action::make('approve')
                        ->label('Approve')
                        ->icon('heroicon-o-hand-thumb-up')
                        ->color('info')
                        ->visible(fn (WithdrawalRequest $r): bool => $r->status === WithdrawalStatus::Pending)
                        ->schema([Textarea::make('note')->rows(2)])
                        ->action(fn (WithdrawalRequest $record, array $data) => self::run(
                            fn () => app(AffiliateService::class)->approveWithdrawal($record, auth('admin')->user(), $data['note'] ?? null),
                            'Approved — now send the money and mark it paid.',
                        )),

                    Action::make('markPaid')
                        ->label('Mark as paid')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (WithdrawalRequest $r): bool => $r->isHolding())
                        ->schema([Textarea::make('note')->label('Reference')->rows(2)])
                        ->requiresConfirmation()
                        ->modalDescription('Only do this once the money has actually left your account.')
                        ->action(fn (WithdrawalRequest $record, array $data) => self::run(
                            fn () => app(AffiliateService::class)->markWithdrawalPaid($record, auth('admin')->user(), $data['note'] ?? null),
                            'Marked as paid',
                        )),

                    Action::make('reject')
                        ->label('Reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn (WithdrawalRequest $r): bool => $r->isHolding())
                        ->schema([Textarea::make('reason')->required()->rows(2)])
                        ->requiresConfirmation()
                        ->modalDescription('The held amount goes back to the customer\'s wallet.')
                        ->action(fn (WithdrawalRequest $record, array $data) => self::run(
                            fn () => app(AffiliateService::class)->rejectWithdrawal($record, auth('admin')->user(), $data['reason']),
                            'Rejected and refunded to the wallet',
                        )),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListWithdrawals::route('/')];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    private static function run(callable $callback, string $success): void
    {
        try {
            $callback();
            Notification::make()->success()->title($success)->send();
        } catch (DomainException $e) {
            Notification::make()->danger()->title('That is not possible')->body($e->getMessage())->send();
        }
    }
}
