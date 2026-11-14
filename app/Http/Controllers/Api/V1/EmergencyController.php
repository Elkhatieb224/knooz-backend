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

class EmergencyController extends Controller
{
    public function __construct(protected NotificationService $notifications)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $items = EmergencyRequest::query()
            ->with(['vehicle'])
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
        $data = $request->validate([
            'kind' => ['required', Rule::in(['tire', 'fuel', 'tow'])],
            'vehicle_id' => ['nullable', 'integer', 'exists:vehicles,id'],
            'lat' => ['required', 'numeric'],
            'lng' => ['required', 'numeric'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'payload' => ['nullable', 'array'],
        ]);

        $emergency = DB::transaction(function () use ($request, $data) {
            $emergency = EmergencyRequest::query()->create([
                'user_id' => $request->user()->id,
                'vehicle_id' => $data['vehicle_id'] ?? null,
                'lat' => $data['lat'],
                'lng' => $data['lng'],
                'address' => $data['address'] ?? null,
                'status' => EmergencyRequestStatus::New,
                'notes' => trim(($data['kind'] ?? '').' | '.($data['notes'] ?? '')),
            ]);

            DB::table('service_request_metas')->insert([
                'emergency_request_id' => $emergency->id,
                'booking_id' => null,
                'request_kind' => $data['kind'],
                'payload' => json_encode($data['payload'] ?? [], JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $emergency;
        });

        $this->notifications->notify(
            $this->notifications->activeAdmins('bookings.view'),
            NotificationType::EmergencyNew,
            'طلب طوارئ جديد',
            'نوع: '.$data['kind'].' — '.$request->user()->name,
            $emergency,
            eventKey: sprintf('emergency:%d:created', $emergency->id),
            queue: false,
        );

        return ApiResponse::success($emergency->load('vehicle'), 'تم إرسال طلب الطوارئ.', 201);
    }

    public function show(Request $request, EmergencyRequest $emergency): JsonResponse
    {
        abort_unless((int) $emergency->user_id === (int) $request->user()->id, 404);

        $meta = DB::table('service_request_metas')
            ->where('emergency_request_id', $emergency->id)
            ->first();

        return ApiResponse::success([
            'request' => $emergency->load('vehicle'),
            'kind' => $meta->request_kind ?? null,
            'payload' => $meta ? json_decode($meta->payload ?: '{}', true) : null,
        ]);
    }
}
