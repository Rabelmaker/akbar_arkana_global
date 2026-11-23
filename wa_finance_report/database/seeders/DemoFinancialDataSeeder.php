<?php

namespace Database\Seeders;

use App\Actions\Finance\ProvisionFinancialFoundation;
use App\Enums\BudgetPeriodType;
use App\Enums\BudgetStatus;
use App\Enums\FinancialAccountType;
use App\Enums\TransactionCategoryType;
use App\Enums\TransactionSource;
use App\Models\Account;
use App\Models\FinancialAccount;
use App\Models\TransactionCategory;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Database\Seeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoFinancialDataSeeder extends Seeder
{
    public function __construct(
        private ProvisionFinancialFoundation $provisionFoundation,
        private TransactionService $transactions,
    ) {}

    public function run(): void
    {
        $user = User::query()
            ->with('activeAccount')
            ->where('email', config('development.demo_user.email'))
            ->first();

        if (! $user?->activeAccount) {
            $this->command?->warn('Demo financial data skipped because the demo user or active account is unavailable.');

            return;
        }

        DB::transaction(function () use ($user): void {
            $account = $user->activeAccount;
            $this->provisionFoundation->handle($account);
            $financialAccounts = $this->seedFinancialAccounts($account);
            $categories = $this->seedCategories($account);
            $this->seedTransactions($account, $user, $financialAccounts, $categories);
            $this->seedBudgets($account, $user, $financialAccounts, $categories);
        });

        $this->command?->info('Demo finance seeded: 2 financial accounts, 9 categories, 24 transactions, and 4 budgets.');
    }

    /** @return array<string, FinancialAccount> */
    private function seedFinancialAccounts(Account $account): array
    {
        $cash = $account->financialAccounts()->where('name', 'Kas Utama')->firstOrFail();
        if ((float) $cash->opening_balance === 0.0
            && (float) $cash->current_balance === 0.0
            && ! $cash->transactions()->exists()
            && ! $cash->incomingTransfers()->exists()) {
            $cash->update([
                'opening_balance' => 2000000,
                'current_balance' => 2000000,
                'notes' => 'Kas operasional untuk data demo.',
            ]);
        }

        $bank = $account->financialAccounts()->firstOrCreate(
            ['name' => 'Bank Operasional'],
            [
                'type' => FinancialAccountType::Bank,
                'institution_name' => 'Bank Nusantara',
                'account_number' => '9988776655',
                'account_holder_name' => $account->name,
                'opening_balance' => 8000000,
                'current_balance' => 8000000,
                'currency' => $account->currency,
                'color' => '#00BAD1',
                'icon' => 'ri-bank-line',
                'is_active' => true,
                'include_in_total' => true,
                'notes' => 'Rekening bank operasional untuk data demo.',
            ],
        );

        return ['cash' => $cash->fresh(), 'bank' => $bank];
    }

    /** @return array<string, TransactionCategory> */
    private function seedCategories(Account $account): array
    {
        $additionalCategories = [
            ['name' => 'Makan dan Minum', 'type' => TransactionCategoryType::Expense, 'icon' => 'ri-restaurant-line', 'color' => '#FF6B6B'],
            ['name' => 'Marketing', 'type' => TransactionCategoryType::Expense, 'icon' => 'ri-megaphone-line', 'color' => '#7367F0'],
            ['name' => 'Sewa Tempat', 'type' => TransactionCategoryType::Expense, 'icon' => 'ri-building-2-line', 'color' => '#A66DD4'],
        ];

        foreach ($additionalCategories as $order => $category) {
            $account->transactionCategories()->firstOrCreate(
                ['slug' => Str::slug($category['name'])],
                [
                    ...$category,
                    'is_default' => false,
                    'is_active' => true,
                    'sort_order' => 20 + $order,
                    'description' => 'Kategori tambahan untuk data demo.',
                ],
            );
        }

        return $account->transactionCategories()
            ->whereIn('name', [
                'Penjualan',
                'Pendapatan Lain',
                'Belanja Operasional',
                'Gaji dan Honor',
                'Transportasi',
                'Utilitas',
                'Makan dan Minum',
                'Marketing',
                'Sewa Tempat',
            ])
            ->get()
            ->keyBy(fn (TransactionCategory $category) => $category->name)
            ->all();
    }

    /**
     * @param  array<string, FinancialAccount>  $financialAccounts
     * @param  array<string, TransactionCategory>  $categories
     */
    private function seedTransactions(
        Account $account,
        User $user,
        array $financialAccounts,
        array $categories,
    ): void {
        $rows = [
            ['income', 'Penjualan', 'cash', 1850000, 0, 'Penjualan tunai produk', 'Toko Sentosa', TransactionSource::Web, 'posted'],
            ['expense', 'Belanja Operasional', 'cash', 325000, 0, 'Pembelian alat tulis kantor', 'Stationery Hub', TransactionSource::Web, 'posted'],
            ['expense', 'Transportasi', 'cash', 85000, 0, 'Transportasi pengiriman lokal', 'Mitra Kurir', TransactionSource::Whatsapp, 'posted'],
            ['income', 'Penjualan', 'bank', 2400000, 0, 'Pembayaran invoice pelanggan A', 'PT Maju Jaya', TransactionSource::Api, 'posted'],
            ['expense', 'Makan dan Minum', 'cash', 150000, 0, 'Konsumsi rapat mingguan', 'Dapur Nusantara', TransactionSource::Whatsapp, 'posted'],
            ['expense', 'Marketing', 'bank', 450000, 2500, 'Iklan media sosial', 'Ads Platform', TransactionSource::N8n, 'posted'],
            ['expense', 'Utilitas', 'bank', 625000, 0, 'Tagihan listrik dan internet', 'Penyedia Utilitas', TransactionSource::Web, 'posted'],
            ['income', 'Pendapatan Lain', 'bank', 750000, 0, 'Pendapatan jasa konsultasi', 'CV Sahabat Usaha', TransactionSource::Api, 'posted'],
            ['expense', 'Sewa Tempat', 'bank', 1500000, 0, 'Sewa ruang kerja bulanan', 'Gedung Bersama', TransactionSource::Web, 'posted'],
            ['expense', 'Gaji dan Honor', 'bank', 2000000, 6500, 'Honor tim proyek tahap pertama', 'Tim Operasional', TransactionSource::Web, 'posted'],
            ['income', 'Penjualan', 'bank', 3200000, 0, 'Pembayaran invoice pelanggan B', 'PT Kreatif Digital', TransactionSource::Api, 'posted'],
            ['expense', 'Belanja Operasional', 'cash', 275000, 0, 'Pembelian bahan kemasan', 'Kemasan Jaya', TransactionSource::Whatsapp, 'posted'],
            ['transfer', null, 'bank', 1000000, 0, 'Pengisian kas operasional', null, TransactionSource::Web, 'posted', 'cash'],
            ['expense', 'Makan dan Minum', 'cash', 210000, 0, 'Konsumsi lembur tim', 'Warung Sejahtera', TransactionSource::Whatsapp, 'posted'],
            ['income', 'Penjualan', 'cash', 1450000, 0, 'Penjualan event akhir pekan', 'Pelanggan Retail', TransactionSource::Web, 'posted'],
            ['expense', 'Transportasi', 'cash', 120000, 0, 'Bensin kendaraan operasional', 'SPBU Kota', TransactionSource::Whatsapp, 'posted'],
            ['expense', 'Marketing', 'bank', 350000, 0, 'Produksi materi promosi', 'Studio Lokal', TransactionSource::N8n, 'posted'],
            ['expense', 'Utilitas', 'bank', 410000, 0, 'Tagihan telepon bisnis', 'Provider Telekomunikasi', TransactionSource::Web, 'posted'],
            ['income', 'Pendapatan Lain', 'bank', 900000, 0, 'Komisi kemitraan', 'Mitra Distribusi', TransactionSource::Api, 'posted'],
            ['expense', 'Gaji dan Honor', 'bank', 1250000, 6500, 'Honor tenaga lepas', 'Freelancer Kreatif', TransactionSource::Web, 'posted'],
            ['expense', 'Belanja Operasional', 'bank', 540000, 0, 'Perawatan peralatan kerja', 'Teknisi Mandiri', TransactionSource::N8n, 'posted'],
            ['transfer', null, 'cash', 500000, 6500, 'Setoran kas ke rekening bank', null, TransactionSource::Web, 'posted', 'bank'],
            ['expense', 'Sewa Tempat', 'bank', 750000, 0, 'Sewa ruang pertemuan', 'Meeting Space', TransactionSource::Whatsapp, 'posted'],
            ['expense', 'Belanja Operasional', 'bank', 3500000, 0, 'Rencana pembelian laptop kerja', 'Tech Store', TransactionSource::Web, 'draft'],
        ];

        $request = Request::create('/database/seed/demo-finance', 'POST', server: [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_USER_AGENT' => 'Laravel DemoFinancialDataSeeder',
        ]);
        $daysInDataset = max(today()->day, 1);

        foreach ($rows as $index => $row) {
            [$type, $category, $sourceAccount, $amount, $fee, $description, $merchant, $source, $status] = $row;
            $destination = $row[9] ?? null;
            $day = (($index * 3) % $daysInDataset) + 1;

            $this->transactions->create(
                $account,
                $user,
                [
                    'type' => $type,
                    'transaction_date' => today()->startOfMonth()->addDays($day - 1)->setTime(9 + ($index % 8), 15),
                    'financial_account_id' => $financialAccounts[$sourceAccount]->id,
                    'destination_financial_account_id' => $destination ? $financialAccounts[$destination]->id : null,
                    'category_id' => $category ? $categories[$category]->id : null,
                    'amount' => $amount,
                    'fee_amount' => $fee,
                    'description' => $description,
                    'merchant' => $merchant,
                    'reference_number' => 'DEMO-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                    'payment_method' => $type === 'transfer' ? 'bank_transfer' : ($sourceAccount === 'cash' ? 'cash' : 'transfer'),
                    'notes' => 'Data demo otomatis.',
                    'status' => $status,
                    'idempotency_key' => 'demo-finance-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                ],
                $request,
                $source,
            );
        }
    }

    /**
     * @param  array<string, FinancialAccount>  $financialAccounts
     * @param  array<string, TransactionCategory>  $categories
     */
    private function seedBudgets(
        Account $account,
        User $user,
        array $financialAccounts,
        array $categories,
    ): void {
        $rows = [
            ['Budget Operasional Bulanan', 'Belanja Operasional', 'bank', 2500000],
            ['Budget Marketing Bulanan', 'Marketing', 'bank', 1500000],
            ['Budget Transportasi Bulanan', 'Transportasi', 'cash', 750000],
            ['Budget Konsumsi Bulanan', 'Makan dan Minum', 'cash', 1000000],
        ];

        foreach ($rows as [$name, $category, $financialAccount, $amount]) {
            $account->budgets()->updateOrCreate(
                ['name' => $name],
                [
                    'category_id' => $categories[$category]->id,
                    'financial_account_id' => $financialAccounts[$financialAccount]->id,
                    'period_type' => BudgetPeriodType::Monthly,
                    'start_date' => today()->startOfMonth(),
                    'end_date' => today()->endOfMonth(),
                    'amount' => $amount,
                    'alert_percentage' => 80,
                    'rollover_enabled' => false,
                    'status' => BudgetStatus::Active,
                    'notes' => 'Budget otomatis untuk data demo.',
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ],
            );
        }
    }
}
