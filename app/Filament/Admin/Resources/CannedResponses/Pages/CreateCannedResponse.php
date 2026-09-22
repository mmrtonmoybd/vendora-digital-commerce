<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CannedResponses\Pages;

use App\Filament\Admin\Resources\CannedResponses\CannedResponseResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateCannedResponse extends CreateRecord
{
    protected static string $resource = CannedResponseResource::class;
}
