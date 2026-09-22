<?php

declare(strict_types=1);

namespace App\Filament\Customer\Resources\Tickets\Pages;

use App\Exceptions\DomainException;
use App\Filament\Customer\Resources\Tickets\TicketResource;
use App\Models\Order;
use App\Services\TicketService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

final class CreateTicket extends CreateRecord
{
    protected static string $resource = TicketResource::class;

    protected static ?string $title = 'Open a support ticket';

    /** @param array<string, mixed> $data */
    protected function handleRecordCreation(array $data): Model
    {
        // Re-read the order through the customer's own scope: the select is a convenience,
        // not the authorisation.
        $order = isset($data['order_id'])
            ? Order::where('user_id', auth()->id())->find($data['order_id'])
            : null;

        try {
            return app(TicketService::class)->open(
                auth()->user(),
                $order,
                (string) $data['subject'],
                (string) $data['message'],
                $data['attachments'] ?? [],
                (string) ($data['priority'] ?? 'normal'),
            );
        } catch (DomainException $e) {
            Notification::make()->danger()->title('Could not open the ticket')->body($e->getMessage())->send();

            $this->halt();
        }
    }
}
