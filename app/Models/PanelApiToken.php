<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class PanelApiToken extends Model
{
    protected $fillable = [
        'tokenable_type',
        'tokenable_id',
        'name',
        'token_hash',
        'panel',
        'last_used_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function tokenable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return array{plain: string, model: self}
     */
    public static function issue(Model $tokenable, string $panel, string $name = 'mobile'): array
    {
        $plain = Str::random(64);

        $model = static::query()->create([
            'tokenable_type' => $tokenable->getMorphClass(),
            'tokenable_id' => $tokenable->getKey(),
            'name' => $name,
            'token_hash' => hash('sha256', $plain),
            'panel' => $panel,
        ]);

        return ['plain' => $plain, 'model' => $model];
    }

    public static function findValidToken(string $plain): ?self
    {
        return static::query()
            ->where('token_hash', hash('sha256', $plain))
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();
    }

    public static function findValidTokenable(string $plain): ?Model
    {
        $token = static::findValidToken($plain);

        if (! $token) {
            return null;
        }

        $token->forceFill(['last_used_at' => now()])->save();

        return $token->tokenable;
    }

    public static function revokePlain(string $plain): void
    {
        static::query()->where('token_hash', hash('sha256', $plain))->delete();
    }
}
