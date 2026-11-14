<?php

namespace App\Models;

use App\Enums\Theme;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Concerns\UsesKnoozNotifications;
use App\Support\Roles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class AdminUser extends Authenticatable
{
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
        'name',
        'email',
        'phone',
        'password',
        'role',
        'status',
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
            'role' => UserRole::class,
            'status' => UserStatus::class,
            'theme' => Theme::class,
        ];
    }

    public function isAdmin(): bool
    {
        return Roles::isAdmin($this->role);
    }

    public function getRoleLabelAttribute(): string
    {
        return $this->role?->label() ?? '';
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status?->label() ?? '';
    }
}
