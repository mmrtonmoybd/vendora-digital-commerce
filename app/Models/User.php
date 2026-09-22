<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserStatus;
use App\Support\Money;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

final class User extends Authenticatable implements FilamentUser, HasAvatar, MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

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
            'email_verified_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
            'balance' => 'integer',
            'marketing_opt_in' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $user): void {
            $user->referral_code ??= self::generateReferralCode();
        });
    }

    public static function generateReferralCode(): string
    {
        do {
            $code = Str::upper(Str::random(8));
        } while (self::withTrashed()->where('referral_code', $code)->exists());

        return $code;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() !== 'customer') {
            return false;
        }

        return $this->status === UserStatus::Active && $this->hasVerifiedEmail();
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar_url ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->avatar_url) : null;
    }

    public function formattedBalance(): string
    {
        return Money::format($this->balance);
    }

    /** @return BelongsTo<self, self> */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(self::class, 'referred_by_id');
    }

    /** @return HasMany<self> */
    public function referrals(): HasMany
    {
        return $this->hasMany(self::class, 'referred_by_id');
    }

    /** @return HasMany<Order> */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /** @return HasMany<Deposit> */
    public function deposits(): HasMany
    {
        return $this->hasMany(Deposit::class);
    }

    /** @return HasMany<WalletTransaction> */
    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    /** @return HasMany<Ticket> */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /** @return HasMany<CustomInvoice> */
    public function customInvoices(): HasMany
    {
        return $this->hasMany(CustomInvoice::class);
    }

    /** @return HasMany<ReferralCommission> */
    public function commissions(): HasMany
    {
        return $this->hasMany(ReferralCommission::class, 'referrer_id');
    }

    /** @return HasMany<WithdrawalRequest> */
    public function withdrawalRequests(): HasMany
    {
        return $this->hasMany(WithdrawalRequest::class);
    }

    /** @return HasMany<LoginHistory> */
    public function loginHistories(): HasMany
    {
        return $this->hasMany(LoginHistory::class, 'authenticatable_id')
            ->where('authenticatable_type', self::class);
    }

    /** @return BelongsToMany<AdminMessage> */
    public function adminMessages(): BelongsToMany
    {
        return $this->belongsToMany(AdminMessage::class)
            ->withPivot(['read_at'])
            ->withTimestamps();
    }

    public function unreadAdminMessagesCount(): int
    {
        return $this->adminMessages()->wherePivotNull('read_at')->count();
    }
}
