<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\EmergencyRequestStatus;
use App\Enums\NotificationType;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ApiResponse;
use App\Models\EmergencyRequest;
use App\Services\Notifications\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * At-home services executed at the customer's chosen place (lat/lng/address).
 * Persisted via emergency_requests + service_request_metas for the admin request hub.
 */
class HomeVisitController extends Controller
{
    public const KINDS = ['wash', 'oil', 'tires'];

    public function __construct(protected NotificationService $notifications)
    {
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'kind' => ['required', Rule::in(self::KINDS)],
            'vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
            'lat' => ['required', 'numeric'],
            'lng' => ['required', 'numeric'],
            'address' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'payload' => ['nullable', 'array'],
        ]);

        $visit = DB::transaction(function () use ($request, $data) {
            $visit = EmergencyRequest::query()->create([
                'user_id' => $request->user()->id,
                'vehicle_id' => $data['vehicle_id'] ?? null,
                'lat' => $data['lat'],
                'lng' => $data['lng'],
                'address' => $data['address'],
                'status' => EmergencyRequestStatus::New,
                'notes' => trim('home:'.$data['kind'].' | '.($data['notes'] ?? '')),
            ]);

            $payload = array_merge($data['payload'] ?? [], [
                'channel' => 'home',
                'place' => [
                    'lat' => (float) $data['lat'],
                    'lng' => (float) $data['lng'],
                    'address' => $data['address'],
                ],
            ]);

            DB::table('service_request_metas')->insert([
                'emergency_request_id' => $visit->id,
                'booking_id' => null,
                'request_kind' => 'home_'.$data['kind'],
                'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $visit;
        });

        $kindLabel = match ($data['kind']) {
            'wash' => 'غسيل',
            'oil' => 'زيت',
            'tires' => 'إطارات',
            default => $data['kind'],
        };

        $this->notifications->notify(
            $this->notifications->activeAdmins('bookings.view'),
            NotificationType::EmergencyNew,
            'طلب خدمة منزلية جديد',
            'نوع: '.$kindLabel.' — '.$request->user()->name.' — '.$data['address'],
            $visit,
            eventKey: sprintf('home_visit:%d:created', $visit->id),
            queue: false,
        );

        $this->notifications->notify(
            $request->user(),
            NotificationType::EmergencyNew,
            'تم استلام طلبك المنزلي',
            'استلمنا طلب '.$kindLabel.' في '.$data['address'].'. نصل إليك خلال 30–60 دقيقة.',
            $visit,
            eventKey: sprintf('home_visit:%d:customer-created', $visit->id),
            queue: false,
        );

        return ApiResponse::success(
            $visit->load('vehicle'),
            'تم إرسال طلب الخدمة المنزلية. سنصل إليك في الموقع المحدد.',
            201,
        );
    }
}
