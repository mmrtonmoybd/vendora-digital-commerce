<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Invoices\Pages;

use App\Filament\Admin\Resources\Invoices\InvoiceResource;
use App\Models\User;
use App\Services\InvoiceService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

final class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    /** @param array<string, mixed> $data */
    protected function handleRecordCreation(array $data): Model
    {
        // Through the service so the invoice number, the status and the customer's
        // notification all happen the one way they are supposed to.
        return app(InvoiceService::class)->create(
            User::findOrFail($data['user_id']),
            (string) $data['product_name'],
            (int) $data['amount'],
            $data['description'] ?? null,
            $data['note'] ?? null,
            isset($data['due_at']) ? Carbon::parse($data['due_at']) : null,
            auth('admin')->user(),
        );
    }
}
