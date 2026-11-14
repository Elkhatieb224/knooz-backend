<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\VehicleStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ApiResponse;
use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VehicleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $vehicles = Vehicle::query()
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id);
                if ($user->company_id) {
                    $q->orWhere('company_id', $user->company_id);
                }
            })
            ->latest()
            ->paginate(min(50, max(1, (int) $request->integer('per_page', 20))));

        return ApiResponse::success(
            $vehicles->items(),
            'OK',
            200,
            [
                'current_page' => $vehicles->currentPage(),
                'last_page' => $vehicles->lastPage(),
                'per_page' => $vehicles->perPage(),
                'total' => $vehicles->total(),
            ]
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'plate_number' => ['required', 'string', 'max:32', 'unique:vehicles,plate_number'],
            'brand' => ['required', 'string', 'max:80'],
            'model' => ['required', 'string', 'max:80'],
            'color' => ['nullable', 'string', 'max:40'],
            'year' => ['nullable', 'integer', 'min:1980', 'max:'.(now()->year + 1)],
        ]);

        $vehicle = Vehicle::query()->create([
            ...$data,
            'user_id' => $request->user()->id,
            'status' => VehicleStatus::Active,
            'qr_code' => 'VEH-'.Str::upper(Str::random(10)),
            'nfc_code' => 'NFC-'.Str::upper(Str::random(10)),
        ]);

        return ApiResponse::success($vehicle, 'تم إضافة المركبة.', 201);
    }

    public function show(Request $request, Vehicle $vehicle): JsonResponse
    {
        $this->authorizeOwned($request, $vehicle);

        return ApiResponse::success($vehicle);
    }

    public function update(Request $request, Vehicle $vehicle): JsonResponse
    {
        $this->authorizeOwned($request, $vehicle);

        $data = $request->validate([
            'plate_number' => ['sometimes', 'string', 'max:32', 'unique:vehicles,plate_number,'.$vehicle->id],
            'brand' => ['sometimes', 'string', 'max:80'],
            'model' => ['sometimes', 'string', 'max:80'],
            'color' => ['nullable', 'string', 'max:40'],
            'year' => ['nullable', 'integer', 'min:1980', 'max:'.(now()->year + 1)],
            'status' => ['sometimes', 'in:active,inactive'],
        ]);

        $vehicle->update($data);

        return ApiResponse::success($vehicle->fresh(), 'تم تحديث المركبة.');
    }

    public function destroy(Request $request, Vehicle $vehicle): JsonResponse
    {
        $this->authorizeOwned($request, $vehicle);
        $vehicle->update(['status' => VehicleStatus::Inactive]);

        return ApiResponse::success(null, 'تم تعطيل المركبة.');
    }

    public function qr(Request $request, Vehicle $vehicle): JsonResponse
    {
        $this->authorizeOwned($request, $vehicle);

        return ApiResponse::success([
            'id' => $vehicle->id,
            'name' => trim($vehicle->brand.' '.$vehicle->model),
            'plate_number' => $vehicle->plate_number,
            'qr_code' => $vehicle->qr_code,
            'nfc_code' => $vehicle->nfc_code,
            'deep_link' => 'knooz://vehicle/'.$vehicle->qr_code,
        ]);
    }

    protected function authorizeOwned(Request $request, Vehicle $vehicle): void
    {
        $user = $request->user();
        $owns = (int) $vehicle->user_id === (int) $user->id
            || ($user->company_id && (int) $vehicle->company_id === (int) $user->company_id);

        abort_unless($owns, 404);
    }
}
