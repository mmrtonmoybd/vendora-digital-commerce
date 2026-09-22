<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\TeamMembers\Pages;

use App\Filament\Admin\Resources\TeamMembers\TeamMemberResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateTeamMember extends CreateRecord
{
    protected static string $resource = TeamMemberResource::class;
}
