<?php

namespace App\Http\Resources\Company;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Vehicle */
class VehicleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'plate_number' => $this->plate_number,
            'brand' => $this->brand,
            'model' => $this->model,
            'color' => $this->color,
            'year' => $this->year,
            'qr_code' => $this->qr_code,
            'nfc_code' => $this->nfc_code,
            'status' => $this->status?->value,
            'status_label' => $this->status_label,
            'display_name' => $this->display_name,
            'operations' => $this->whenLoaded(
                'operations',
                fn () => OperationResource::collection($this->operations)
            ),
            'created_at' => optional($this->created_at)?->toIso8601String(),
        ];
    }
}
