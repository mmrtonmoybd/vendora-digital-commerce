<?php

declare(strict_types=1);

namespace App\Filament\Customer\Resources\Deposits\Pages;

use App\Filament\Customer\Resources\Deposits\DepositResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListDeposits extends ListRecords
{
    protected static string $resource = DepositResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Add money')];
    }
}
