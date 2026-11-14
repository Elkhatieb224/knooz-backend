<?php

namespace App\Http\Resources\Center;

use App\Enums\CenterUserRole;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\CenterUser */
class CenterUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $role = $this->role ?? CenterUserRole::Manager;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status?->value,
            'role' => $role->value,
            'role_label' => $role->label(),
            'theme' => $this->theme?->value,
            'avatar' => $this->avatar,
            'can_view_revenue' => $this->canViewRevenue(),
            'center' => $this->whenLoaded('center', function () {
                return [
                    'id' => $this->center->id,
                    'name' => $this->center->name,
                    'type' => $this->center->type?->value,
                    'status' => $this->center->status?->value,
                    'city' => $this->center->city,
                    'show_revenue' => $this->center->showsRevenue(),
                ];
            }),
        ];
    }
}
