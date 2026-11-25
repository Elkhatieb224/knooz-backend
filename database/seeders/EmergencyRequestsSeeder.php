<?php

namespace Database\Seeders;

use App\Enums\EmergencyRequestStatus;
use App\Models\EmergencyRequest;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmergencyRequestsSeeder extends Seeder
{
    public function run(): void
    {
        $ahmed = User::query()->where('email', 'ahmed@customer.test')->first();
        $vehicle = Vehicle::query()->where('plate_number', 'أ ب ج 1234')->first();
        $employee = User::query()->where('email', 'emergency@knooz.test')->first();

        if (! $ahmed || ! $vehicle) {
            $this->command?->warn('EmergencyRequestsSeeder skipped: missing dependencies.');

            return;
        }

        $request = EmergencyRequest::query()->updateOrCreate(
            [
                'user_id' => $ahmed->id,
                'vehicle_id' => $vehicle->id,
                'address' => 'طريق المطار، الرياض',
            ],
            [
                'lat' => 24.7200000,
                'lng' => 46.6800000,
                'status' => EmergencyRequestStatus::Assigned,
                'employee_id' => $employee?->id,
                'notes' => 'tire | ثقب في الإطار الأمامي',
            ]
        );

        $exists = DB::table('service_request_metas')
            ->where('emergency_request_id', $request->id)
            ->exists();

        if (! $exists) {
            DB::table('service_request_metas')->insert([
                'emergency_request_id' => $request->id,
                'booking_id' => null,
                'request_kind' => 'tire',
                'payload' => json_encode([
                    'channel' => 'emergency',
                    'note' => 'ثقب في الإطار الأمامي',
                ], JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command?->info('Emergency requests seeded.');
    }
}
