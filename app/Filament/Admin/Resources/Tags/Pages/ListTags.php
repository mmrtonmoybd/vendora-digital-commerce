<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Tags\Pages;

use App\Filament\Admin\Resources\Tags\TagResource;
use Filament\Resources\Pages\ListRecords;

final class ListTags extends ListRecords
{
    protected static string $resource = TagResource::class;
}
