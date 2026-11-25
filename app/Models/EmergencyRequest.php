<?php

namespace App\Models;

use App\Enums\EmergencyRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmergencyRequest extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'vehicle_id',
        'center_id',
        'lat',
        'lng',
        'address',
        'status',
        'employee_id',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => EmergencyRequestStatus::class,
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status?->label() ?? '';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }
}
