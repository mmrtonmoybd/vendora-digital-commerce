<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\SlugRedirects\Pages;

use App\Filament\Admin\Resources\SlugRedirects\SlugRedirectResource;
use Filament\Resources\Pages\ListRecords;

final class ListSlugRedirects extends ListRecords
{
    protected static string $resource = SlugRedirectResource::class;
}
