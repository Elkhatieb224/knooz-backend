<?php

namespace App\Http\Resources\Company;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'status' => $this->status?->value,
            'status_label' => $this->status_label,
            'payment_status' => $this->payment_status?->value,
            'payment_status_label' => $this->payment_status_label,
            'amount' => (float) $this->amount,
            'notes' => $this->notes,
            'started_at' => optional($this->started_at)?->toIso8601String(),
            'completed_at' => optional($this->completed_at)?->toIso8601String(),
            'vehicle' => $this->whenLoaded('vehicle', fn () => $this->vehicle ? [
                'id' => $this->vehicle->id,
                'plate_number' => $this->vehicle->plate_number,
                'brand' => $this->vehicle->brand,
                'model' => $this->vehicle->model,
            ] : null),
            'service' => $this->whenLoaded('service', fn () => $this->service ? [
                'id' => $this->service->id,
                'name_ar' => $this->service->name_ar,
                'name_en' => $this->service->name_en,
            ] : null),
            'center' => $this->whenLoaded('center', fn () => $this->center ? [
                'id' => $this->center->id,
                'name' => $this->center->name,
                'city' => $this->center->city,
            ] : null),
            'created_at' => optional($this->created_at)?->toIso8601String(),
        ];
    }
}
