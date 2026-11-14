<?php

namespace App\Models;

use App\Enums\CenterUserRole;
use App\Enums\Theme;
use App\Enums\UserStatus;
use App\Models\Concerns\UsesKnoozNotifications;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class CenterUser extends Authenticatable implements CanResetPasswordContract
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
        'center_id',
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
            'role' => CenterUserRole::class,
            'theme' => Theme::class,
        ];
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function createdOperations(): HasMany
    {
        return $this->hasMany(Operation::class, 'created_by_center_user_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status?->label() ?? '';
    }

    public function getRoleLabelAttribute(): string
    {
        return $this->role?->label() ?? '';
    }

    public function isManager(): bool
    {
        return ($this->role ?? CenterUserRole::Manager) === CenterUserRole::Manager;
    }

    public function canViewRevenue(): bool
    {
        return ($this->role ?? CenterUserRole::Manager)->canViewRevenue();
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new \App\Notifications\CenterResetPasswordNotification($token));
    }
}
