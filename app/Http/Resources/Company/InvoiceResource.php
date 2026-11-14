<?php

namespace App\Http\Resources\Company;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Invoice */
class InvoiceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'period_start' => optional($this->period_start)?->toDateString(),
            'period_end' => optional($this->period_end)?->toDateString(),
            'total' => (float) $this->total,
            'status' => $this->status?->value,
            'status_label' => $this->status_label,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'description' => $item->description,
                'amount' => (float) $item->amount,
                'vehicle' => $item->relationLoaded('vehicle') && $item->vehicle ? [
                    'id' => $item->vehicle->id,
                    'plate_number' => $item->vehicle->plate_number,
                ] : null,
                'service' => $item->relationLoaded('service') && $item->service ? [
                    'id' => $item->service->id,
                    'name_ar' => $item->service->name_ar,
                    'name_en' => $item->service->name_en,
                ] : null,
            ])->values()),
            'created_at' => optional($this->created_at)?->toIso8601String(),
        ];
    }
}
