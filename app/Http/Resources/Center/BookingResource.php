<?php

namespace App\Http\Resources\Center;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Booking */
class BookingResource extends JsonResource
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
            'scheduled_at' => optional($this->scheduled_at)?->toIso8601String(),
            'notes' => $this->notes,
            'price' => $this->price !== null ? (float) $this->price : null,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'phone' => $this->user->phone,
            ]),
            'vehicle' => $this->whenLoaded('vehicle', fn () => [
                'id' => $this->vehicle->id,
                'plate_number' => $this->vehicle->plate_number,
                'brand' => $this->vehicle->brand,
                'model' => $this->vehicle->model,
            ]),
            'service' => $this->whenLoaded('service', fn () => [
                'id' => $this->service->id,
                'name_ar' => $this->service->name_ar,
                'name_en' => $this->service->name_en,
            ]),
            'created_at' => optional($this->created_at)?->toIso8601String(),
        ];
    }
}
