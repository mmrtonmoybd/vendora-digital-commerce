<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Faqs\Pages;

use App\Filament\Admin\Resources\Faqs\FaqResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateFaq extends CreateRecord
{
    protected static string $resource = FaqResource::class;
}
