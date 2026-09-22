<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Commissions;

use App\Enums\CommissionStatus;
use App\Filament\Admin\Resources\Commissions\Pages\ListCommissions;
use App\Filament\Support\MoneyColumn;
use App\Models\ReferralCommission;
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

final class CommissionResource extends Resource
{
    protected static ?string $model = ReferralCommission::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGift;

    protected static string|null|UnitEnum $navigationGroup = 'Affiliate';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Commissions';

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('referrer.name')->label('Referrer')->searchable(),
                TextColumn::make('referredUser.name')->label('Referred customer')->searchable(),
                TextColumn::make('order.order_number')->label('Order')->copyable(),
                MoneyColumn::make('base_amount')->label('Order value'),
                TextColumn::make('rate_snapshot')->label('Rate')->badge()->color('gray')
                    ->tooltip('The rate at the time. Changing the programme later never rewrites this.'),
                MoneyColumn::make('amount')->label('Commission'),
                TextColumn::make('status')->badge(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([SelectFilter::make('status')->options(CommissionStatus::class)->multiple()])
            ->recordActions([
                ActionGroup::make([
                    Action::make('approve')
                        ->label('Approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (ReferralCommission $r): bool => $r->status === CommissionStatus::Pending)
                        ->requiresConfirmation()
                        ->modalDescription('This credits the referrer\'s wallet.')
                        ->action(function (ReferralCommission $record): void {
                            app(AffiliateService::class)->approve($record, auth('admin')->user());

                            Notification::make()->success()->title('Commission approved')->send();
                        }),
                    Action::make('reverse')
                        ->label('Reverse')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('danger')
                        ->visible(fn (ReferralCommission $r): bool => $r->status !== CommissionStatus::Reversed)
                        ->schema([Textarea::make('reason')->required()->rows(2)])
                        ->requiresConfirmation()
                        ->modalDescription('If it was already paid, the amount is taken back out of the wallet.')
                        ->action(function (ReferralCommission $record, array $data): void {
                            app(AffiliateService::class)->reverse($record, $data['reason'], auth('admin')->user());

                            Notification::make()->success()->title('Commission reversed')->send();
                        }),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListCommissions::route('/')];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
