<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Enums\AccessStatus;
use App\Exceptions\DomainException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\AccessFulfillmentService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use UnitEnum;

/**
 * The fulfilment desk.
 *
 * One row per purchased unit, not per order, because that is the unit of work: an order
 * for three different products is three invitations to send. The columns are chosen so an
 * operator can work without opening anything — the access email and the checkout fields are
 * copyable in place, and the SLA timer says which row to do next.
 */
final class AccessQueue extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|null|UnitEnum $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Access queue';

    protected string $view = 'filament.admin.pages.access-queue';

    public static function getNavigationBadge(): ?string
    {
        $count = OrderItem::query()->whereIn('access_status', [
            AccessStatus::Awaiting->value,
            AccessStatus::InProgress->value,
            AccessStatus::ProblemReported->value,
        ])->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'danger';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->baseQuery())
            ->defaultSort('order_paid_at')
            ->recordActionsPosition(RecordActionsPosition::BeforeCells)
            ->paginationPageOptions([10, 25, 50, 100])
            ->columns([
                TextColumn::make('order.order_number')->label('Order')->searchable()->copyable()->weight('semibold'),
                TextColumn::make('product_snapshot.name')->label('Product')->wrap()->limit(40)
                    ->description(fn (OrderItem $record): ?string => $record->platformName()),
                TextColumn::make('delivery_email')
                    ->label('Grant access to')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Email copied')
                    ->weight('bold')
                    ->color('primary'),
                TextColumn::make('field_values')
                    ->label('Checkout details')
                    ->wrap()
                    ->copyable()
                    ->placeholder('—')
                    ->state(fn (OrderItem $record): ?string => collect($record->field_values ?? [])
                        ->filter()
                        ->map(fn ($value, $key): string => str($key)->headline().': '.$value)
                        ->implode(' · ') ?: null),
                TextColumn::make('quantity')->label('Qty'),
                TextColumn::make('access_status')->label('Status')->badge(),
                TextColumn::make('sla')
                    ->label('Waiting')
                    ->badge()
                    ->state(fn (OrderItem $record): string => $record->access_status->isOpen()
                        ? $record->minutesWaiting().' min'
                        : '—')
                    // Green inside target, amber approaching it, red past it.
                    ->color(fn (OrderItem $record): string => match (true) {
                        ! $record->access_status->isOpen() => 'gray',
                        $record->isBreachingSla() => 'danger',
                        $record->minutesWaiting() > $record->slaMinutes() * 0.6 => 'warning',
                        default => 'success',
                    }),
                TextColumn::make('order.assignedAdmin.name')->label('Assigned')->placeholder('Nobody')->toggleable(),
                TextColumn::make('order.paid_at')->label('Paid')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('access_status')->label('Status')->options(AccessStatus::class)->multiple(),
                SelectFilter::make('assigned')
                    ->label('Assigned to')
                    ->relationship('order.assignedAdmin', 'name')
                    ->preload(),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('assignToMe')
                        ->label('Assign to me')
                        ->icon('heroicon-o-user-plus')
                        ->visible(fn (OrderItem $record): bool => $record->access_status->isOpen())
                        ->action(function (OrderItem $record): void {
                            app(AccessFulfillmentService::class)->assign($record->order, auth('admin')->user());

                            Notification::make()->success()->title('Assigned to you')->send();
                        }),

                    Action::make('start')
                        ->label('Start')
                        ->icon('heroicon-o-play')
                        ->visible(fn (OrderItem $record): bool => $record->access_status === AccessStatus::Awaiting)
                        ->action(fn (OrderItem $record) => $this->run(fn () => app(AccessFulfillmentService::class)
                            ->start($record, auth('admin')->user()), 'Marked as in progress')),

                    Action::make('grant')
                        ->label('Mark access granted')
                        ->icon('heroicon-o-key')
                        ->color('success')
                        ->visible(fn (OrderItem $record): bool => ! $record->access_status->isLive()
                            || $record->access_status === AccessStatus::ProblemReported)
                        ->schema([
                            Textarea::make('note')
                                ->label('Note for the customer')
                                ->rows(2)
                                ->helperText('Shown on their order page and in the access email.'),
                            TextInput::make('reference')
                                ->label('Internal reference')
                                ->maxLength(190)
                                ->helperText('Which asset you shared. Staff only — never shown to the customer.'),
                            Toggle::make('send_email')->label('Email the customer')->default(true),
                        ])
                        ->action(fn (OrderItem $record, array $data) => $this->run(
                            fn () => app(AccessFulfillmentService::class)->grant(
                                $record,
                                auth('admin')->user(),
                                $data['note'] ?? null,
                                $data['reference'] ?? null,
                                (bool) ($data['send_email'] ?? true),
                            ),
                            'Access granted',
                        )),

                    Action::make('reInvite')
                        ->label('Re-invite done')
                        ->icon('heroicon-o-arrow-path')
                        ->visible(fn (OrderItem $record): bool => $record->access_status->isLive()
                            || $record->access_status === AccessStatus::ProblemReported)
                        ->schema([Textarea::make('note')->rows(2)])
                        ->action(fn (OrderItem $record, array $data) => $this->run(
                            fn () => app(AccessFulfillmentService::class)->reInvite($record, auth('admin')->user(), $data['note'] ?? null),
                            'Customer notified',
                        )),

                    Action::make('replace')
                        ->label('Replace access')
                        ->icon('heroicon-o-arrows-right-left')
                        ->color('warning')
                        ->visible(fn (): bool => auth('admin')->user()?->can('replace_access') ?? false)
                        ->schema([
                            TextInput::make('reference')->label('New internal reference')->required()->maxLength(190),
                            Textarea::make('note')->label('Note for the customer')->rows(2),
                        ])
                        ->action(fn (OrderItem $record, array $data) => $this->run(
                            fn () => app(AccessFulfillmentService::class)->replace($record, auth('admin')->user(), $data['reference'], $data['note'] ?? null),
                            'Replacement recorded',
                        )),

                    Action::make('changeEmail')
                        ->label('Change delivery email')
                        ->icon('heroicon-o-envelope')
                        ->visible(fn (): bool => auth('admin')->user()?->can('change_delivery_email') ?? false)
                        ->schema([
                            TextInput::make('email')->email()->required()
                                ->default(fn (OrderItem $record): string => $record->delivery_email),
                            Textarea::make('reason')->required()->rows(2),
                        ])
                        ->action(fn (OrderItem $record, array $data) => $this->run(
                            fn () => app(AccessFulfillmentService::class)->changeDeliveryEmail(
                                $record,
                                $data['email'],
                                auth('admin')->user(),
                                $data['reason'],
                            ),
                            'Delivery email updated',
                        )),

                    Action::make('revoke')
                        ->label('Revoke access')
                        ->icon('heroicon-o-no-symbol')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->schema([Textarea::make('reason')->required()->rows(2)])
                        ->action(fn (OrderItem $record, array $data) => $this->run(
                            fn () => app(AccessFulfillmentService::class)->revoke($record, auth('admin')->user(), $data['reason']),
                            'Access revoked',
                        )),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('grantAll')
                        ->label('Mark granted')
                        ->icon('heroicon-o-key')
                        ->color('success')
                        ->schema([
                            Textarea::make('note')->label('Note for the customer')->rows(2),
                            Toggle::make('send_email')->label('Email the customers')->default(true),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $service = app(AccessFulfillmentService::class);
                            $admin = auth('admin')->user();
                            $granted = 0;

                            foreach ($records as $record) {
                                try {
                                    $service->grant($record, $admin, $data['note'] ?? null, null, (bool) ($data['send_email'] ?? true));
                                    $granted++;
                                } catch (DomainException) {
                                    // Already granted, or not in a state that allows it.
                                }
                            }

                            Notification::make()->success()->title("{$granted} items granted")->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public function getTabs(): array
    {
        $count = fn (AccessStatus ...$statuses): int => OrderItem::query()
            ->whereIn('access_status', array_map(fn (AccessStatus $s): string => $s->value, $statuses))
            ->count();

        return [
            'awaiting' => Tab::make('Awaiting')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('access_status', AccessStatus::Awaiting->value))
                ->badge($count(AccessStatus::Awaiting))
                ->badgeColor('warning'),
            'in_progress' => Tab::make('In progress')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('access_status', AccessStatus::InProgress->value))
                ->badge($count(AccessStatus::InProgress)),
            'problem' => Tab::make('Problem reported')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('access_status', AccessStatus::ProblemReported->value))
                ->badge($count(AccessStatus::ProblemReported))
                ->badgeColor('danger'),
            'granted' => Tab::make('Granted, unconfirmed')
                ->modifyQueryUsing(fn (Builder $q) => $q->whereIn('access_status', [
                    AccessStatus::Granted->value,
                    AccessStatus::ReGranted->value,
                    AccessStatus::Replaced->value,
                ])),
            'confirmed' => Tab::make('Confirmed')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('access_status', AccessStatus::Confirmed->value)),
            'all' => Tab::make('All'),
        ];
    }

    /**
     * Only paid orders can be fulfilled, so unpaid ones never clutter the queue.
     *
     * @return Builder<OrderItem>
     */
    private function baseQuery(): Builder
    {
        return OrderItem::query()
            ->select('order_items.*')
            /*
             | The queue is worked oldest-payment-first, but ORDER BY orders.paid_at needs
             | the orders table in the query and whereHas only produces a subquery — so the
             | sort referenced a table that was never joined and the page 500'd.
             |
             | A correlated subselect sorts on the same value without a join, which keeps
             | the Eloquent hydration of order_items intact and reads through the orders
             | primary key.
             */
            ->addSelect([
                'order_paid_at' => Order::query()
                    ->select('paid_at')
                    ->whereColumn('orders.id', 'order_items.order_id')
                    ->limit(1),
            ])
            ->with(['order.assignedAdmin', 'order.user', 'product.platform'])
            ->whereHas('order', fn (Builder $q) => $q->paid());
    }

    private function run(callable $callback, string $success): void
    {
        try {
            $callback();
            Notification::make()->success()->title($success)->send();
        } catch (DomainException $e) {
            Notification::make()->danger()->title('That is not possible')->body($e->getMessage())->send();
        }
    }
}
