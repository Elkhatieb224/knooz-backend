<?php

namespace App\Models;

use App\Enums\CompanyUserRole;
use App\Enums\Theme;
use App\Enums\UserStatus;
use App\Models\Concerns\UsesKnoozNotifications;
use App\Support\CompanyPermissions;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class CompanyUser extends Authenticatable implements CanResetPasswordContract
{
    use CanResetPassword;
    use HasFactory, SoftDeletes;
    use Notifiable, UsesKnoozNotifications {
        UsesKnoozNotifications::notifications insteadof Notifiable;
        UsesKnoozNotifications::readNotifications insteadof Notifiable;
        UsesKnoozNotifications::unreadNotifications insteadof Notifiable;
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'email',
        'phone',
        'password',
        'status',
        'role',
        'theme',
        'avatar',
        'last_login_at',
        'legacy_user_id',
        'created_by',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
            'role' => CompanyUserRole::class,
            'theme' => Theme::class,
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(self::class, 'created_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status?->label() ?? '';
    }

    public function getRoleLabelAttribute(): string
    {
        return $this->role?->label() ?? '';
    }

    public function isOwner(): bool
    {
        return ($this->role ?? CompanyUserRole::Owner) === CompanyUserRole::Owner;
    }

    public function can($abilities, $arguments = []): bool
    {
        if (is_string($abilities) && $arguments === []) {
            return CompanyPermissions::allows($this, $abilities);
        }

        return parent::can($abilities, $arguments);
    }

    /**
     * @return list<string>
     */
    public function permissions(): array
    {
        return CompanyPermissions::forRole($this);
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new \App\Notifications\CompanyResetPasswordNotification($token));
    }
}
