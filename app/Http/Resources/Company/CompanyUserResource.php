<?php

namespace App\Http\Resources\Company;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\CompanyUser */
class CompanyUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status?->value,
            'role' => $this->role?->value,
            'role_label' => $this->role_label,
            'permissions' => $this->permissions(),
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'theme' => $this->theme?->value,
            'avatar' => $this->avatar,
            'company' => $this->whenLoaded('company', function () {
                return $this->company ? [
                    'id' => $this->company->id,
                    'name' => $this->company->name,
                    'status' => $this->company->status?->value,
                    'payment_mode' => $this->company->payment_mode?->value,
                    'phone' => $this->company->phone,
                    'email' => $this->company->email,
                ] : null;
            }),
        ];
    }
}
