<?php

declare(strict_types=1);

namespace App\Filament\Customer\Resources\Tickets\Pages;

use App\Filament\Customer\Resources\Tickets\TicketResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListTickets extends ListRecords
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('New ticket')];
    }
}
