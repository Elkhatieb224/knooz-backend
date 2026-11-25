<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\EmergencyRequestStatus;
use App\Enums\NotificationType;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ApiResponse;
use App\Models\EmergencyRequest;
use App\Models\Service;
use App\Services\Centers\CenterDispatchService;
use App\Services\Notifications\NotificationService;
use App\Support\ServiceChannels;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EmergencyController extends Controller
{
    public function __construct(
        protected NotificationService $notifications,
        protected CenterDispatchService $dispatch,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $items = EmergencyRequest::query()
            ->with(['vehicle', 'center'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return ApiResponse::success($items->items(), 'OK', 200, [
            'current_page' => $items->currentPage(),
            'last_page' => $items->lastPage(),
            'total' => $items->total(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $allowedKinds = Service::query()
            ->where('is_active', true)
            ->where('channel', ServiceChannels::EMERGENCY)
            ->whereNotNull('kind_key')
            ->pluck('kind_key')
            ->unique()
            ->values()
            ->all();

        if ($allowedKinds === []) {
            $allowedKinds = ['tire', 'fuel', 'tow', 'battery', 'lockout'];
        }

        $data = $request->validate([
            'kind' => ['required', Rule::in($allowedKinds)],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
            'lat' => ['required', 'numeric'],
            'lng' => ['required', 'numeric'],
            'address' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'payload' => ['nullable', 'array'],
            'destination' => ['nullable', 'array'],
            'destination.lat' => ['nullable', 'numeric'],
            'destination.lng' => ['nullable', 'numeric'],
            'destination.address' => ['nullable', 'string', 'max:255'],
            'plate' => ['nullable', 'string', 'max:32'],
            'vehicle_type' => ['nullable', 'string', 'max:40'],
        ]);

        $service = null;
        if (! empty($data['service_id'])) {
            $service = Service::query()
                ->whereKey($data['service_id'])
                ->where('is_active', true)
                ->where('channel', ServiceChannels::EMERGENCY)
                ->first();
        }
        if ($service === null) {
            $service = Service::query()
                ->where('is_active', true)
                ->where('channel', ServiceChannels::EMERGENCY)
                ->where('kind_key', $data['kind'])
                ->orderBy('sort_order')
                ->first();
        }

        $requires = $service?->requiredFields() ?? ['location'];
        $this->assertRequirements($requires, $data);

        $assignedCenter = $this->dispatch->nearestForChannel(
            ServiceChannels::EMERGENCY,
            (float) $data['lat'],
            (float) $data['lng'],
            $service?->id,
        );

        $payload = array_merge($data['payload'] ?? [], [
            'channel' => ServiceChannels::EMERGENCY,
            'service_id' => $service?->id,
            'service_name' => $service?->name_ar,
            'kind_key' => $data['kind'],
            'assigned_center_id' => $assignedCenter?->id,
            'place' => [
                'lat' => (float) $data['lat'],
                'lng' => (float) $data['lng'],
                'address' => $data['address'],
            ],
        ]);
        if (! empty($data['destination'])) {
            $payload['destination'] = $data['destination'];
        }
        if (! empty($data['plate'])) {
            $payload['plate'] = $data['plate'];
        }
        if (! empty($data['vehicle_type'])) {
            $payload['vehicle_type'] = $data['vehicle_type'];
        }
        if (empty($payload['vehicle_type']) && ! empty(($data['payload']['vehicle_type'] ?? null))) {
            $payload['vehicle_type'] = $data['payload']['vehicle_type'];
        }
        if (empty($payload['plate']) && ! empty(($data['payload']['plate'] ?? null))) {
            $payload['plate'] = $data['payload']['plate'];
        }

        $emergency = DB::transaction(function () use ($request, $data, $payload, $assignedCenter) {
            $emergency = EmergencyRequest::query()->create([
                'user_id' => $request->user()->id,
                'vehicle_id' => $data['vehicle_id'] ?? null,
                'center_id' => $assignedCenter?->id,
                'lat' => $data['lat'],
                'lng' => $data['lng'],
                'address' => $data['address'],
                'status' => EmergencyRequestStatus::New,
                'notes' => trim(($data['kind'] ?? '').' | '.($data['notes'] ?? '')),
            ]);

            DB::table('service_request_metas')->insert([
                'emergency_request_id' => $emergency->id,
                'booking_id' => null,
                'request_kind' => $data['kind'],
                'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $emergency;
        });

        $kindLabel = $service?->name_ar ?? $data['kind'];

        $this->notifications->notify(
            $this->notifications->activeAdmins('bookings.view'),
            NotificationType::EmergencyNew,
            'طلب طوارئ جديد',
            'نوع: '.$kindLabel.' — '.$request->user()->name.' — '.$data['address'],
            $emergency,
            eventKey: sprintf('emergency:%d:created', $emergency->id),
            queue: false,
        );

        if ($assignedCenter) {
            $this->notifications->notify(
                $this->notifications->centerUsers((int) $assignedCenter->id),
                NotificationType::EmergencyNew,
                'طلب طوارئ جديد لمركزكم',
                'نوع: '.$kindLabel.' — '.$request->user()->name.' — '.$data['address'],
                $emergency,
                eventKey: sprintf('emergency:%d:center-assigned', $emergency->id),
                queue: false,
            );
        }

        $this->notifications->notify(
            $request->user(),
            NotificationType::EmergencyNew,
            'تم استلام طلب الطوارئ',
            'استلمنا طلب «'.$kindLabel.'» وسنصل إليك في موقعك.',
            $emergency,
            eventKey: sprintf('emergency:%d:customer-created', $emergency->id),
            queue: false,
        );

        return ApiResponse::success($emergency->load(['vehicle', 'center']), 'تم إرسال طلب الطوارئ.', 201);
    }

    /**
     * @param  list<string>  $requires
     * @param  array<string, mixed>  $data
     */
    protected function assertRequirements(array $requires, array $data): void
    {
        $errors = [];
        $plate = $data['plate']
            ?? ($data['payload']['plate'] ?? null);
        $vehicleType = $data['vehicle_type']
            ?? ($data['payload']['vehicle_type'] ?? null);

        if (in_array('vehicle', $requires, true)
            && empty($data['vehicle_id'])
            && empty($vehicleType)
        ) {
            $errors['vehicle_type'] = ['حدد نوع المركبة.'];
        }
        if ((in_array('plate', $requires, true) || in_array('vehicle', $requires, true))
            && empty($plate)
            && empty($data['vehicle_id'])
        ) {
            $errors['plate'] = ['أدخل رقم اللوحة.'];
        }
        if (in_array('destination', $requires, true)) {
            $dest = $data['destination'] ?? null;
            if (! is_array($dest) || empty($dest['lat']) || empty($dest['lng'])) {
                $errors['destination'] = ['هذه الخدمة تتطلب تحديد وجهة النقل.'];
            }
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    public function show(Request $request, EmergencyRequest $emergency): JsonResponse
    {
        abort_unless((int) $emergency->user_id === (int) $request->user()->id, 404);

        $meta = DB::table('service_request_metas')
            ->where('emergency_request_id', $emergency->id)
            ->first();

        return ApiResponse::success([
            'request' => $emergency->load(['vehicle', 'center']),
            'kind' => $meta->request_kind ?? null,
            'payload' => $meta ? json_decode($meta->payload ?: '{}', true) : null,
        ]);
    }
}
