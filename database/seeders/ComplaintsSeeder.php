<?php

namespace Database\Seeders;

use App\Enums\ComplaintStatus;
use App\Models\Complaint;
use App\Models\User;
use Illuminate\Database\Seeder;

class ComplaintsSeeder extends Seeder
{
    public function run(): void
    {
        $ahmed = User::query()->where('email', 'ahmed@customer.test')->first();
        $sara = User::query()->where('email', 'sara@customer.test')->first();

        if ($ahmed) {
            Complaint::query()->updateOrCreate(
                [
                    'user_id' => $ahmed->id,
                    'subject' => 'تأخير في الموعد',
                ],
                [
                    'body' => 'تأخر تنفيذ الحجز عن الموعد المحدد بساعة.',
                    'status' => ComplaintStatus::Open,
                    'assigned_to' => null,
                ]
            );
        }

        if ($sara) {
            Complaint::query()->updateOrCreate(
                [
                    'user_id' => $sara->id,
                    'subject' => 'جودة الغسيل',
                ],
                [
                    'body' => 'الجودة لم تكن بالمستوى المتوقع.',
                    'status' => ComplaintStatus::InProgress,
                    'assigned_to' => null,
                    'admin_notes' => 'تم التواصل مع المركز',
                ]
            );
        }

        $this->command?->info('Complaints seeded.');
    }
}
