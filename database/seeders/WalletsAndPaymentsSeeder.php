<?php

namespace Database\Seeders;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\WalletTransactionType;
use App\Models\Company;
use App\Models\Operation;
use App\Models\Payment;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Database\Seeder;

class WalletsAndPaymentsSeeder extends Seeder
{
    public function run(): void
    {
        $ahmed = User::query()->where('email', 'ahmed@customer.test')->first();
        $sara = User::query()->where('email', 'sara@customer.test')->first();
        $company = Company::query()->where('commercial_register', 'CR-1234567890')->first();

        $customerWallet = null;
        if ($ahmed) {
            $customerWallet = Wallet::query()->updateOrCreate(
                ['user_id' => $ahmed->id],
                ['balance' => 150]
            );

            WalletTransaction::query()->firstOrCreate(
                [
                    'wallet_id' => $customerWallet->id,
                    'description' => 'شحن رصيد أولي',
                ],
                [
                    'type' => WalletTransactionType::Credit,
                    'amount' => 200,
                    'balance_after' => 200,
                    'created_by' => null,
                ]
            );

            $operation = Operation::query()
                ->where('user_id', $ahmed->id)
                ->where('notes', 'تمت العملية بنجاح')
                ->first();

            WalletTransaction::query()->firstOrCreate(
                [
                    'wallet_id' => $customerWallet->id,
                    'description' => 'خصم عملية غسيل',
                ],
                [
                    'type' => WalletTransactionType::Debit,
                    'amount' => 50,
                    'balance_after' => 150,
                    'reference_type' => $operation ? Operation::class : null,
                    'reference_id' => $operation?->id,
                    'created_by' => null,
                ]
            );

            Payment::query()->updateOrCreate(
                [
                    'user_id' => $ahmed->id,
                    'amount' => 200,
                    'notes' => 'تم قبول التحويل',
                ],
                [
                    'wallet_id' => $customerWallet->id,
                    'method' => PaymentMethod::BankTransfer,
                    'status' => PaymentStatus::Approved,
                    'receipt_path' => 'receipts/sample-transfer.pdf',
                    'reviewed_at' => now()->subDay(),
                ]
            );
        }

        if ($company) {
            Wallet::query()->updateOrCreate(
                ['company_id' => $company->id],
                ['balance' => 5000]
            );

            Payment::query()->updateOrCreate(
                [
                    'company_id' => $company->id,
                    'amount' => 1500,
                    'notes' => 'دفع إلكتروني',
                ],
                [
                    'method' => PaymentMethod::Online,
                    'status' => PaymentStatus::Paid,
                ]
            );
        }

        if ($sara) {
            Payment::query()->updateOrCreate(
                [
                    'user_id' => $sara->id,
                    'amount' => 120,
                    'notes' => 'بانتظار المراجعة',
                ],
                [
                    'method' => PaymentMethod::BankTransfer,
                    'status' => PaymentStatus::Pending,
                    'receipt_path' => 'receipts/pending-transfer.jpg',
                ]
            );
        }

        $this->command?->info('Wallets & payments seeded.');
    }
}
