<?php

namespace App\Services\Center;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Center;
use App\Models\Vehicle;
use Illuminate\Validation\ValidationException;

class OperationLookupService
{
    /**
     * @return array<string, mixed>
     */
    public function lookup(Center $center, string $method, string $value): array
    {
        $method = strtolower(trim($method));
        $value = trim($value);

        if ($value === '') {
            throw ValidationException::withMessages([
                'value' => ['قيمة البحث مطلوبة.'],
            ]);
        }

        $vehicle = match ($method) {
            'qr' => Vehicle::query()->where('qr_code', $value)->first(),
            'nfc' => Vehicle::query()->where('nfc_code', $value)->first(),
            'plate' => Vehicle::query()->where('plate_number', $value)->first(),
            default => throw ValidationException::withMessages([
                'method' => ['طريقة البحث غير مدعومة. استخدم qr أو nfc أو plate.'],
            ]),
        };

        if (! $vehicle) {
            throw ValidationException::withMessages([
                'value' => ['لم يتم العثور على مركبة مطابقة.'],
            ]);
        }

        $vehicle->load(['user', 'company']);

        $services = $center->services()
            ->wherePivot('is_available', true)
            ->where('services.is_active', true)
            ->get()
            ->map(fn ($service) => [
                'id' => $service->id,
                'name_ar' => $service->name_ar,
                'name_en' => $service->name_en,
                'base_price' => (float) $service->base_price,
                'contract_price' => $service->pivot->contract_price !== null
                    ? (float) $service->pivot->contract_price
                    : null,
                'is_available' => (bool) $service->pivot->is_available,
            ])
            ->values()
            ->all();

        $openBooking = Booking::query()
            ->with(['service', 'user'])
            ->where('center_id', $center->id)
            ->where('vehicle_id', $vehicle->id)
            ->whereIn('status', [BookingStatus::New, BookingStatus::InProgress])
            ->latest('scheduled_at')
            ->first();

        $isCompany = $vehicle->company_id !== null;

        return [
            'vehicle' => [
                'id' => $vehicle->id,
                'plate_number' => $vehicle->plate_number,
                'brand' => $vehicle->brand,
                'model' => $vehicle->model,
                'color' => $vehicle->color,
                'year' => $vehicle->year,
                'qr_code' => $vehicle->qr_code,
                'nfc_code' => $vehicle->nfc_code,
                'status' => $vehicle->status?->value,
            ],
            'owner' => $vehicle->user ? [
                'id' => $vehicle->user->id,
                'name' => $vehicle->user->name,
                'phone' => $vehicle->user->phone,
                'email' => $vehicle->user->email,
            ] : null,
            'is_company' => $isCompany,
            'company' => $vehicle->company ? [
                'id' => $vehicle->company->id,
                'name' => $vehicle->company->name,
            ] : null,
            'available_services' => $services,
            'open_booking' => $openBooking ? [
                'id' => $openBooking->id,
                'status' => $openBooking->status?->value,
                'scheduled_at' => optional($openBooking->scheduled_at)?->toIso8601String(),
                'service_id' => $openBooking->service_id,
                'notes' => $openBooking->notes,
            ] : null,
            // D5: company verification OTP not enabled in v1.
            'requires_verification' => false,
            'verification_message' => $isCompany
                ? 'تحقق الشركات غير مفعّل في هذه النسخة؛ يمكن المتابعة دون كود تحقق.'
                : null,
            'lookup_method' => $method,
        ];
    }
}
