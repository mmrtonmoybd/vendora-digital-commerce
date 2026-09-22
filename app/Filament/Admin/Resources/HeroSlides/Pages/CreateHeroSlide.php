<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\HeroSlides\Pages;

use App\Filament\Admin\Resources\HeroSlides\HeroSlideResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateHeroSlide extends CreateRecord
{
    protected static string $resource = HeroSlideResource::class;
}
