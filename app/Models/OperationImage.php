<?php

namespace App\Models;

use App\Enums\OperationImageType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperationImage extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'operation_id',
        'path',
        'type',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => OperationImageType::class,
        ];
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type?->label() ?? '';
    }

    public function operation(): BelongsTo
    {
        return $this->belongsTo(Operation::class);
    }
}
