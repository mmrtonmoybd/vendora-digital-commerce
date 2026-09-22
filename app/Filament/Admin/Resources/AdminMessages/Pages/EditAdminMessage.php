<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\AdminMessages\Pages;

use App\Filament\Admin\Resources\AdminMessages\AdminMessageResource;
use Filament\Resources\Pages\EditRecord;

final class EditAdminMessage extends EditRecord
{
    protected static string $resource = AdminMessageResource::class;
}
