<?php

namespace App\Http\Resources\Center;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin \App\Models\Operation */
class OperationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_id' => $this->booking_id,
            'status' => $this->status?->value,
            'status_label' => $this->status_label,
            'payment_status' => $this->payment_status?->value,
            'payment_status_label' => $this->payment_status_label,
            'amount' => (float) $this->amount,
            'source' => $this->source,
            'notes' => $this->notes,
            'started_at' => optional($this->started_at)?->toIso8601String(),
            'completed_at' => optional($this->completed_at)?->toIso8601String(),
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
            'created_by' => $this->whenLoaded('createdByCenterUser', fn () => $this->createdByCenterUser ? [
                'id' => $this->createdByCenterUser->id,
                'name' => $this->createdByCenterUser->name,
                'role' => $this->createdByCenterUser->role?->value,
            ] : null),
            'images' => $this->whenLoaded('images', fn () => $this->images->map(fn ($img) => [
                'id' => $img->id,
                'type' => $img->type?->value,
                'path' => $img->path,
                'url' => Storage::disk('public')->url($img->path),
            ])),
            'created_at' => optional($this->created_at)?->toIso8601String(),
        ];
    }
}
