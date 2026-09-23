<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Tickets;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Filament\Admin\Resources\Tickets\Pages\ListTickets;
use App\Filament\Admin\Resources\Tickets\Pages\ViewTicket;
use App\Filament\Admin\Resources\Tickets\Schemas\TicketInfolist;
use App\Models\Ticket;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

final class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLifebuoy;

    protected static string|null|UnitEnum $navigationGroup = 'Support';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'subject';

    public static function getNavigationBadge(): ?string
    {
        $count = Ticket::query()->needsAttention()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'danger';
    }

    public static function infolist(Schema $schema): Schema
    {
        return TicketInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('last_reply_at', 'desc')
            ->paginationPageOptions([10, 25, 50, 100])
            ->columns([
                TextColumn::make('ticket_number')->label('Ticket')->searchable()->copyable()->weight('semibold'),
                TextColumn::make('subject')->searchable()->wrap()->limit(60)
                    ->description(fn (Ticket $record): string => $record->user?->name ?? ''),
                TextColumn::make('order.order_number')->label('Order')->placeholder('—')->toggleable(),
                TextColumn::make('priority')->badge(),
                TextColumn::make('status')->badge(),
                TextColumn::make('sla')
                    ->label('Age')
                    ->badge()
                    ->state(fn (Ticket $record): string => $record->ageInHours().'h')
                    // Red once the first-response target for that priority has passed.
                    ->color(fn (Ticket $record): string => $record->isBreachingSla() ? 'danger' : 'gray'),
                TextColumn::make('assignedAdmin.name')->label('Assigned')->placeholder('Nobody')->toggleable(),
                TextColumn::make('last_reply_at')->label('Last reply')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(TicketStatus::class)->multiple(),
                SelectFilter::make('priority')->options(TicketPriority::class)->multiple(),
                SelectFilter::make('assigned_admin')->label('Assigned to')->relationship('assignedAdmin', 'name')->preload(),
                Filter::make('unassigned')
                    ->label('Unassigned')
                    ->query(fn (Builder $query): Builder => $query->whereNull('assigned_admin_id')),
                Filter::make('breaching_sla')
                    ->label('Past first-response target')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereNull('first_responded_at')
                        ->whereNotIn('status', [TicketStatus::Closed->value])),
                Filter::make('created_at')
                    ->schema([
                        DatePicker::make('from')->label('Opened from')->native(false),
                        DatePicker::make('until')->label('Opened until')->native(false),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'] ?? null, fn (Builder $q, string $d): Builder => $q->whereDate('created_at', '>=', $d))
                        ->when($data['until'] ?? null, fn (Builder $q, string $d): Builder => $q->whereDate('created_at', '<=', $d)))
                    ->indicateUsing(fn (array $data): array => array_values(array_filter([
                        ($data['from'] ?? null) ? 'Opened from '.$data['from'] : null,
                        ($data['until'] ?? null) ? 'Opened until '.$data['until'] : null,
                    ]))),
            ])
            ->recordActions([ActionGroup::make([ViewAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTickets::route('/'),
            'view' => ViewTicket::route('/{record}'),
        ];
    }

    /** @return array<int, string> */
    public static function getGloballySearchableAttributes(): array
    {
        return ['ticket_number', 'subject'];
    }

    public static function canCreate(): bool
    {
        // A ticket is a customer asking for help. Staff reply to them; they do not invent
        // them on the customer's behalf.
        return false;
    }
}
