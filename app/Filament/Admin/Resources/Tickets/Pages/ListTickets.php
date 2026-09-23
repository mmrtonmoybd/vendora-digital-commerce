<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Tickets\Pages;

use App\Enums\TicketStatus;
use App\Filament\Admin\Resources\Tickets\TicketResource;
use App\Models\Ticket;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

final class ListTickets extends ListRecords
{
    protected static string $resource = TicketResource::class;

    /** "All" first: the default tab must not carry a query modifier. */
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')->badge(Ticket::count()),
            'needs_reply' => Tab::make('Needs reply')
                ->modifyQueryUsing(fn (Builder $q) => $q->whereIn('status', Ticket::needsAttentionStatuses()))
                ->badge(Ticket::query()->needsAttention()->count())
                ->badgeColor('danger'),
            'mine' => Tab::make('Assigned to me')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('assigned_admin_id', auth('admin')->id())),
            'answered' => Tab::make('Answered')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', TicketStatus::Answered->value)),
            'closed' => Tab::make('Closed')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', TicketStatus::Closed->value)),
        ];
    }
}
