<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\HeroSlides\Pages;

use App\Filament\Admin\Resources\HeroSlides\HeroSlideResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditHeroSlide extends EditRecord
{
    protected static string $resource = HeroSlideResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
