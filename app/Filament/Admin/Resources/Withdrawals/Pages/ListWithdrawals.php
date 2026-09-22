<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Withdrawals\Pages;

use App\Filament\Admin\Resources\Withdrawals\WithdrawalResource;
use Filament\Resources\Pages\ListRecords;

final class ListWithdrawals extends ListRecords
{
    protected static string $resource = WithdrawalResource::class;
}
