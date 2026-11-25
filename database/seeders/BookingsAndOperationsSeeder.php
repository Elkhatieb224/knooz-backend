<?php

namespace Database\Seeders;

use App\Enums\BookingStatus;
use App\Enums\OperationImageType;
use App\Enums\OperationPaymentStatus;
use App\Enums\OperationStatus;
use App\Models\Booking;
use App\Models\Center;
use App\Models\Operation;
use App\Models\OperationImage;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class BookingsAndOperationsSeeder extends Seeder
{
    public function run(): void
    {
        $ahmed = User::query()->where('email', 'ahmed@customer.test')->first();
        $sara = User::query()->where('email', 'sara@customer.test')->first();
        $contact = User::query()->where('email', 'fleet-contact@knooz.test')->first();
        $branchEmployee = User::query()->where('email', 'employee@knooz.test')->first();

        $vehicleAhmed = Vehicle::query()->where('plate_number', 'أ ب ج 1234')->first();
        $vehicleSara = Vehicle::query()->where('plate_number', 'د هـ و 5678')->first();
        $companyVehicle = Vehicle::query()->where('plate_number', 'ش ر ك 1001')->first();

        $ownedCenter = Center::query()->where('email', 'riyadh@knooz.test')->first();
        $contractedCenter = Center::query()->where('email', 'jeddah@partner.test')->first();

        $wash = Service::query()->where('slug', 'car-wash')->first();
        $oil = Service::query()->where('slug', 'oil-change')->first();
        $tires = Service::query()->where('slug', 'tires')->first();

        if (! $ahmed || ! $vehicleAhmed || ! $ownedCenter || ! $wash) {
            $this->command?->warn('BookingsAndOperationsSeeder skipped: missing dependencies.');

            return;
        }

        $booking = Booking::query()->updateOrCreate(
            [
                'user_id' => $ahmed->id,
                'vehicle_id' => $vehicleAhmed->id,
                'service_id' => $wash->id,
                'notes' => 'يفضل غسيل كامل',
            ],
            [
                'center_id' => $ownedCenter->id,
                'scheduled_at' => now()->addDay()->setTime(10, 0),
                'status' => BookingStatus::New,
                'price' => $wash->base_price,
            ]
        );

        if ($sara && $vehicleSara && $contractedCenter && $oil) {
            Booking::query()->updateOrCreate(
                [
                    'user_id' => $sara->id,
                    'vehicle_id' => $vehicleSara->id,
                    'service_id' => $oil->id,
                ],
                [
                    'center_id' => $contractedCenter->id,
                    'scheduled_at' => now()->addDays(2)->setTime(14, 30),
                    'status' => BookingStatus::InProgress,
                    'price' => $oil->base_price,
                ]
            );
        }

        $operation = Operation::query()->updateOrCreate(
            [
                'booking_id' => $booking->id,
                'service_id' => $wash->id,
            ],
            [
                'user_id' => $ahmed->id,
                'vehicle_id' => $vehicleAhmed->id,
                'center_id' => $ownedCenter->id,
                'employee_id' => $branchEmployee?->id,
                'status' => OperationStatus::Completed,
                'amount' => $wash->base_price,
                'payment_status' => OperationPaymentStatus::Paid,
                'started_at' => now()->subHours(3),
                'completed_at' => now()->subHours(2),
                'notes' => 'تمت العملية بنجاح',
            ]
        );

        OperationImage::query()->firstOrCreate(
            [
                'operation_id' => $operation->id,
                'type' => OperationImageType::Before,
            ],
            ['path' => 'operations/sample-before.jpg']
        );

        OperationImage::query()->firstOrCreate(
            [
                'operation_id' => $operation->id,
                'type' => OperationImageType::After,
            ],
            ['path' => 'operations/sample-after.jpg']
        );

        if ($contact && $companyVehicle && $tires && $branchEmployee) {
            Operation::query()->updateOrCreate(
                [
                    'user_id' => $contact->id,
                    'vehicle_id' => $companyVehicle->id,
                    'service_id' => $tires->id,
                    'notes' => null,
                ],
                [
                    'center_id' => $ownedCenter->id,
                    'employee_id' => $branchEmployee->id,
                    'status' => OperationStatus::Completed,
                    'amount' => $tires->base_price,
                    'payment_status' => OperationPaymentStatus::Credit,
                    'started_at' => now()->subDay(),
                    'completed_at' => now()->subDay()->addHour(),
                ]
            );
        }

        $this->command?->info('Bookings & operations seeded.');
    }
}
