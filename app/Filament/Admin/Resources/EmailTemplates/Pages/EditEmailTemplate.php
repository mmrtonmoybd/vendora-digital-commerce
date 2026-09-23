<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\EmailTemplates\Pages;

use App\Filament\Admin\Resources\EmailTemplates\EmailTemplateResource;
use App\Models\EmailTemplate;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Cache;

/** @property-read EmailTemplate $record */
final class EditEmailTemplate extends EditRecord
{
    protected static string $resource = EmailTemplateResource::class;

    protected function afterSave(): void
    {
        // Templates are cached for half an hour; without this the next email would still
        // use the old wording and the operator would think the save failed.
        Cache::forget("email-template:{$this->record->key}");
    }
}
