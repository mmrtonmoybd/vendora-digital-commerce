<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserStatus;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Jeffgreco13\FilamentBreezy\Traits\TwoFactorAuthenticatable;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Traits\HasRoles;

final class Admin extends Authenticatable implements FilamentUser, HasAvatar
{
    /** @use HasFactory<\Database\Factories\AdminFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    /** Roles and permissions for staff live on their own guard. */
    protected string $guard_name = 'admin';

    protected $guarded = ['id'];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'status' => UserStatus::class,
            'last_login_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'admin' && $this->status === UserStatus::Active;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar_url ? Storage::disk('public')->url($this->avatar_url) : null;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    /** @return HasMany<LoginHistory> */
    public function loginHistories(): HasMany
    {
        return $this->hasMany(LoginHistory::class, 'authenticatable_id')
            ->where('authenticatable_type', self::class);
    }
}
