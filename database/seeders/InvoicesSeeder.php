<?php

namespace Database\Seeders;

use App\Enums\InvoiceStatus;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Service;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class InvoicesSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->where('commercial_register', 'CR-1234567890')->first();
        $vehicle = Vehicle::query()->where('plate_number', 'ش ر ك 1001')->first();
        $tires = Service::query()->where('slug', 'tires')->first();

        if (! $company || ! $vehicle || ! $tires) {
            $this->command?->warn('InvoicesSeeder skipped: missing dependencies.');

            return;
        }

        $number = 'INV-'.now()->format('Ym').'-001';

        $invoice = Invoice::query()->updateOrCreate(
            ['number' => $number],
            [
                'company_id' => $company->id,
                'period_start' => now()->startOfMonth()->toDateString(),
                'period_end' => now()->endOfMonth()->toDateString(),
                'total' => 200,
                'status' => InvoiceStatus::Issued,
            ]
        );

        InvoiceItem::query()->updateOrCreate(
            [
                'invoice_id' => $invoice->id,
                'description' => 'خدمة كفرات - باترول',
            ],
            [
                'vehicle_id' => $vehicle->id,
                'service_id' => $tires->id,
                'amount' => 200,
            ]
        );

        $this->command?->info('Invoices seeded.');
    }
}
