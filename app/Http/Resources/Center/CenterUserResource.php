<?php

namespace App\Http\Resources\Center;

use App\Enums\CenterUserRole;
use App\Support\ServiceChannels;
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
                $center = $this->center;
                $offersCenter = (bool) $center->offers_center_service;
                $offersHome = (bool) $center->offers_home_service;
                $offersEmergency = (bool) $center->offers_emergency_service;

                $services = [];
                if ($center->relationLoaded('services')) {
                    $services = $center->services->map(fn ($service) => [
                        'id' => $service->id,
                        'name_ar' => $service->name_ar,
                        'name_en' => $service->name_en,
                        'slug' => $service->slug,
                        'channel' => $service->channel,
                        'kind_key' => $service->kind_key,
                        'flow_hint' => $service->flow_hint,
                        'icon' => $service->icon,
                        'is_available' => (bool) ($service->pivot?->is_available ?? true),
                    ])->values()->all();
                }

                return [
                    'id' => $center->id,
                    'name' => $center->name,
                    'type' => $center->type?->value,
                    'status' => $center->status?->value,
                    'city' => $center->city,
                    'show_revenue' => $center->showsRevenue(),
                    'channels' => [
                        ServiceChannels::CENTER => $offersCenter,
                        ServiceChannels::HOME => $offersHome,
                        ServiceChannels::EMERGENCY => $offersEmergency,
                    ],
                    'offers_center_service' => $offersCenter,
                    'offers_home_service' => $offersHome,
                    'offers_emergency_service' => $offersEmergency,
                    'capabilities' => [
                        'branch_operations' => $offersCenter,
                        'bookings' => $offersCenter || $offersHome,
                        'home_visits' => $offersHome,
                        'emergency' => $offersEmergency,
                        'operations' => $offersCenter,
                        'products' => $offersCenter,
                        'reports' => $offersCenter || $offersHome || $offersEmergency,
                    ],
                    'services' => $services,
                ];
            }),
        ];
    }
}
