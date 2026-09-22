<?php

declare(strict_types=1);

namespace App\Filament\Customer\Resources\WalletTransactions\Pages;

use App\Filament\Customer\Resources\WalletTransactions\WalletTransactionResource;
use Filament\Resources\Pages\ListRecords;

final class ListWalletTransactions extends ListRecords
{
    protected static string $resource = WalletTransactionResource::class;
}
