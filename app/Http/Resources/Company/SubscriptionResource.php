<?php

namespace App\Http\Resources\Company;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Subscription */
class SubscriptionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status?->value,
            'status_label' => $this->status_label,
            'starts_at' => optional($this->starts_at)?->toIso8601String(),
            'ends_at' => optional($this->ends_at)?->toIso8601String(),
            'operations_used' => $this->operations_used,
            'package' => $this->whenLoaded('package', fn () => $this->package ? [
                'id' => $this->package->id,
                'name' => $this->package->name,
                'type' => $this->package->type?->value,
                'duration_days' => $this->package->duration_days,
                'operations_count' => $this->package->operations_count,
                'vehicles_count' => $this->package->vehicles_count,
                'price' => (float) $this->package->price,
            ] : null),
        ];
    }
}
