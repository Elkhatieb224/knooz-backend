<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CenterStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ApiResponse;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\Center;
use App\Models\Offer;
use App\Services\Notifications\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __invoke(Request $request, NotificationService $notifications): JsonResponse
    {
        $user = $request->user()->load('wallet');

        $lat = $request->float('lat');
        $lng = $request->float('lng');

        $centersQuery = Center::query()
            ->where('status', CenterStatus::Active)
            ->with(['services' => fn ($q) => $q->where('is_active', true)]);

        $centers = $centersQuery->limit(20)->get()->map(function (Center $center) use ($lat, $lng) {
            $distance = null;
            if ($lat && $lng && $center->lat && $center->lng) {
                $distance = $this->haversineKm($lat, $lng, (float) $center->lat, (float) $center->lng);
            }

            return [
                'id' => $center->id,
                'name' => $center->name,
                'city' => $center->city,
                'address' => $center->address,
                'lat' => $center->lat,
                'lng' => $center->lng,
                'distance_km' => $distance !== null ? round($distance, 2) : null,
                'services_count' => $center->services->count(),
            ];
        })->sortBy(fn ($c) => $c['distance_km'] ?? 9999)->values();

        $offers = Offer::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->latest()
            ->limit(10)
            ->get(['id', 'title', 'description', 'discount_percent', 'image_path', 'service_id']);

        return ApiResponse::success([
            'user' => new UserResource($user),
            'wallet_balance' => (float) ($user->wallet?->balance ?? 0),
            'loyalty_points' => (int) $user->loyalty_points,
            'unread_notifications' => $notifications->unreadCount($user),
            'offers' => $offers,
            'nearest_centers' => $centers->take(5)->values(),
        ]);
    }

    protected function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earth * (2 * atan2(sqrt($a), sqrt(1 - $a)));
    }
}
