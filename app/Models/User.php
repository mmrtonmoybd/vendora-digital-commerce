<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserStatus;
use App\Support\Money;
use Database\Factories\UserFactory;
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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Jeffgreco13\FilamentBreezy\Traits\TwoFactorAuthenticatable;

/**
 * @generated Property annotations, refreshed by scripts/model-properties.php.
 * Larastan cannot read casts declared in a casts() method on a $guarded model, so the
 * enum and date types have to be written down for it — and the IDE benefits too.
 *
 * @property int $id
 * @property string $name
 * @property ?string $username
 * @property string $email
 * @property ?string $locale
 * @property ?string $currency
 * @property ?string $phone
 * @property ?Carbon $email_verified_at
 * @property ?string $password
 * @property ?string $avatar_url
 * @property int $balance
 * @property string $referral_code
 * @property ?int $referred_by_id
 * @property ?int $signup_source_id
 * @property UserStatus $status
 * @property ?string $google_id
 * @property ?string $two_factor_secret
 * @property ?string $two_factor_recovery_codes
 * @property ?Carbon $two_factor_confirmed_at
 * @property bool $marketing_opt_in
 * @property ?Carbon $last_login_at
 * @property ?string $last_login_ip
 * @property ?string $remember_token
 * @property ?Carbon $deleted_at
 * @property ?Carbon $created_at
 * @property ?Carbon $updated_at
 */
final class User extends Authenticatable implements FilamentUser, HasAvatar, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

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
        self::creating(function (self $user): void {
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
        return $this->avatar_url ? Storage::disk('public')->url($this->avatar_url) : null;
    }

    public function formattedBalance(): string
    {
        return Money::format($this->balance);
    }

    /**
     * Which platform's ad brought this customer to the shop.
     *
     * @return BelongsTo<OrderSource, $this>
     */
    public function signupSource(): BelongsTo
    {
        return $this->belongsTo(OrderSource::class, 'signup_source_id');
    }

    /** @return BelongsTo<self, $this> */
    public function referrer(): BelongsTo
    {
        return $this->belongsTo(self::class, 'referred_by_id');
    }

    /** @return HasMany<self, $this> */
    public function referrals(): HasMany
    {
        return $this->hasMany(self::class, 'referred_by_id');
    }

    /** @return HasMany<Order, $this> */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /** @return HasMany<Deposit, $this> */
    public function deposits(): HasMany
    {
        return $this->hasMany(Deposit::class);
    }

    /** @return HasMany<WalletTransaction, $this> */
    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    /** @return HasMany<Ticket, $this> */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /** @return HasMany<CustomInvoice, $this> */
    public function customInvoices(): HasMany
    {
        return $this->hasMany(CustomInvoice::class);
    }

    /** @return HasMany<ReferralCommission, $this> */
    public function commissions(): HasMany
    {
        return $this->hasMany(ReferralCommission::class, 'referrer_id');
    }

    /** @return HasMany<WithdrawalRequest, $this> */
    public function withdrawalRequests(): HasMany
    {
        return $this->hasMany(WithdrawalRequest::class);
    }

    /** @return HasMany<LoginHistory, $this> */
    public function loginHistories(): HasMany
    {
        return $this->hasMany(LoginHistory::class, 'authenticatable_id')
            ->where('authenticatable_type', self::class);
    }

    /** @return BelongsToMany<AdminMessage, $this> */
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
