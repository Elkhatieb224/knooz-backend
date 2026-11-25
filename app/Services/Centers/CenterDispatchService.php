<?php

namespace App\Services\Centers;

use App\Enums\CenterStatus;
use App\Models\Center;
use App\Support\ServiceChannels;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CenterDispatchService
{
    /**
     * Find the nearest active center that offers the given execution channel.
     * Optionally require that a specific service is attached and available.
     */
    public function nearestForChannel(
        string $channel,
        ?float $lat = null,
        ?float $lng = null,
        ?int $serviceId = null,
    ): ?Center {
        $centers = $this->eligibleQuery($channel, $serviceId)->get();

        // Prefer centers that explicitly offer the catalog service; if none,
        // fall back to any active center enabled for this execution channel.
        if ($centers->isEmpty() && $serviceId !== null) {
            $centers = $this->eligibleQuery($channel, null)->get();
        }

        if ($centers->isEmpty()) {
            return null;
        }

        if ($lat === null || $lng === null) {
            return $centers->first();
        }

        return $centers
            ->map(function (Center $center) use ($lat, $lng) {
                $distance = null;
                if ($center->lat !== null && $center->lng !== null) {
                    $distance = $this->haversineKm($lat, $lng, (float) $center->lat, (float) $center->lng);
                }

                $center->setAttribute('dispatch_distance_km', $distance);

                return $center;
            })
            ->sortBy(fn (Center $c) => $c->getAttribute('dispatch_distance_km') ?? 999999)
            ->first();
    }

    /**
     * @return Collection<int, Center>
     */
    public function listForChannel(string $channel, ?int $serviceId = null): Collection
    {
        return $this->eligibleQuery($channel, $serviceId)->orderBy('name')->get();
    }

    public function assertCenterServes(Center $center, string $channel, ?int $serviceId = null): bool
    {
        if ($center->status !== CenterStatus::Active) {
            return false;
        }

        if (! $center->offersChannel($channel)) {
            return false;
        }

        if ($serviceId === null) {
            return true;
        }

        return $center->services()
            ->where('services.id', $serviceId)
            ->wherePivot('is_available', true)
            ->where('services.is_active', true)
            ->exists();
    }

    protected function eligibleQuery(string $channel, ?int $serviceId = null): Builder
    {
        $query = Center::query()
            ->where('status', CenterStatus::Active)
            ->offeringChannel($channel);

        if ($serviceId !== null) {
            $query->whereHas('services', function ($q) use ($serviceId) {
                $q->where('services.id', $serviceId)
                    ->where('services.is_active', true)
                    ->where('center_service.is_available', true);
            });
        }

        return $query;
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

    public static function channelColumn(string $channel): ?string
    {
        return match ($channel) {
            ServiceChannels::CENTER => 'offers_center_service',
            ServiceChannels::HOME => 'offers_home_service',
            ServiceChannels::EMERGENCY => 'offers_emergency_service',
            default => null,
        };
    }
}
