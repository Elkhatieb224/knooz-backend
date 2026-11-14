<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $wallet = $this->relationLoaded('wallet') ? $this->wallet : $this->wallet()->first();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role?->value,
            'role_label' => $this->role_label,
            'status' => $this->status?->value,
            'avatar' => $this->avatar ? url('storage/'.$this->avatar) : null,
            'qr_code' => $this->qr_code,
            'nfc_code' => $this->nfc_code,
            'theme' => $this->theme?->value ?? 'light',
            'company_id' => $this->company_id,
            'wallet_balance' => (float) ($wallet?->balance ?? 0),
            'loyalty_points' => (int) ($this->loyalty_points ?? 0),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
