<?php

namespace App\Models;

use App\Enums\OperationPaymentStatus;
use App\Enums\OperationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Operation extends Model
{
    use Concerns\BelongsToCenter;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'booking_id',
        'user_id',
        'vehicle_id',
        'center_id',
        'service_id',
        'employee_id',
        'created_by_center_user_id',
        'status',
        'amount',
        'payment_status',
        'started_at',
        'completed_at',
        'notes',
        'source',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OperationStatus::class,
            'payment_status' => OperationPaymentStatus::class,
            'amount' => 'decimal:2',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status?->label() ?? '';
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return $this->payment_status?->label() ?? '';
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(Center::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function createdByCenterUser(): BelongsTo
    {
        return $this->belongsTo(CenterUser::class, 'created_by_center_user_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(OperationImage::class);
    }
}
