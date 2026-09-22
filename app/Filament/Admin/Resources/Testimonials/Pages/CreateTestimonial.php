<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Testimonials\Pages;

use App\Filament\Admin\Resources\Testimonials\TestimonialResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateTestimonial extends CreateRecord
{
    protected static string $resource = TestimonialResource::class;
}
