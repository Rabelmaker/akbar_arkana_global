<?php

namespace App\Services;

use App\Enums\TransactionSource;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\FinancialAccount;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;

class TransactionService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(
        Account $account,
        User $user,
        array $attributes,
        Request $request,
        TransactionSource $source = TransactionSource::Web,
    ): Transaction {
        return DB::transaction(function () use ($account, $user, $attributes, $request, $source): Transaction {
            $account = Account::query()->lockForUpdate()->findOrFail($account->id);
            $attachments = $attributes['attachments'] ?? [];
            unset($attributes['attachments']);

            if (! empty($attributes['idempotency_key'])) {
                $existing = $account->transactions()
                    ->where('idempotency_key', $attributes['idempotency_key'])
                    ->first();
                if ($existing) {
                    return $existing;
                }
            }

            $requestedStatus = TransactionStatus::from($attributes['status']);
            $this->validateCategory($account, $attributes);
            $transaction = $account->transactions()->create([
                ...$attributes,
                'uuid' => (string) Str::uuid(),
                'transaction_number' => $this->nextNumber(),
                'fee_amount' => $attributes['fee_amount'] ?? 0,
                'total_amount' => $this->total($attributes),
                'currency' => $account->currency,
                'exchange_rate' => 1,
                'status' => TransactionStatus::Draft,
                'source' => $source,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            $this->storeAttachments($transaction, $attachments, $user, $source);
            $this->log($transaction, $user, $request, 'created', 'Transaction created');

            if ($requestedStatus === TransactionStatus::Posted) {
                return $this->post($transaction, $user, $request);
            }

            return $transaction->refresh();
        });
    }

    /** @param array<string, mixed> $attributes */
    public function update(Transaction $transaction, User $user, array $attributes, Request $request): Transaction
    {
        return DB::transaction(function () use ($transaction, $user, $attributes, $request): Transaction {
            $locked = Transaction::query()->lockForUpdate()->findOrFail($transaction->id);
            $this->ensureStatus($locked, TransactionStatus::Draft);
            $attachments = $attributes['attachments'] ?? [];
            unset($attributes['attachments'], $attributes['status']);
            $this->validateCategory($locked->account, $attributes);
            $old = $locked->only(array_keys($attributes));
            $locked->update([
                ...$attributes,
                'fee_amount' => $attributes['fee_amount'] ?? 0,
                'total_amount' => $this->total($attributes),
                'updated_by' => $user->id,
            ]);
            $this->storeAttachments($locked, $attachments, $user);
            $this->log($locked, $user, $request, 'updated', 'Transaction updated', [
                'old' => $old,
                'attributes' => $locked->only(array_keys($attributes)),
            ]);

            return $locked->refresh();
        });
    }

    public function post(Transaction $transaction, User $user, Request $request): Transaction
    {
        return DB::transaction(function () use ($transaction, $user, $request): Transaction {
            $account = Account::query()->lockForUpdate()->findOrFail($transaction->account_id);
            $locked = Transaction::query()->lockForUpdate()->findOrFail($transaction->id);
            $this->ensureStatus($locked, TransactionStatus::Draft);
            $this->ensureMonthlyQuota($account);
            $this->applyBalanceEffect($locked);
            $locked->update([
                'status' => TransactionStatus::Posted,
                'approved_by' => $user->id,
                'approved_at' => now(),
                'updated_by' => $user->id,
            ]);
            $this->log($locked, $user, $request, 'posted', 'Transaction posted');

            return $locked->refresh();
        });
    }

    public function cancel(Transaction $transaction, User $user, Request $request, ?string $reason = null): Transaction
    {
        return DB::transaction(function () use ($transaction, $user, $request, $reason): Transaction {
            $locked = Transaction::query()->lockForUpdate()->findOrFail($transaction->id);
            $this->ensureStatus($locked, TransactionStatus::Posted);
            $this->applyBalanceEffect($locked, true);
            $locked->update([
                'status' => TransactionStatus::Cancelled,
                'cancellation_reason' => $reason,
                'updated_by' => $user->id,
            ]);
            $this->log($locked, $user, $request, 'cancelled', 'Transaction cancelled', ['reason' => $reason]);

            return $locked->refresh();
        });
    }

    public function reverse(Transaction $transaction, User $user, Request $request, ?string $reason = null): Transaction
    {
        return DB::transaction(function () use ($transaction, $user, $request, $reason): Transaction {
            $original = Transaction::query()->lockForUpdate()->findOrFail($transaction->id);
            $this->ensureStatus($original, TransactionStatus::Posted);

            $reversal = $original->account->transactions()->create([
                ...$original->only([
                    'type', 'transaction_date', 'financial_account_id',
                    'destination_financial_account_id', 'category_id', 'amount',
                    'fee_amount', 'total_amount', 'currency', 'exchange_rate',
                    'merchant', 'reference_number', 'payment_method',
                ]),
                'uuid' => (string) Str::uuid(),
                'transaction_number' => $this->nextNumber(),
                'description' => 'Reversal: '.$original->description,
                'notes' => $reason,
                'status' => TransactionStatus::Posted,
                'source' => TransactionSource::System,
                'reversed_transaction_id' => $original->id,
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            $this->applyBalanceEffect($reversal, true);
            $original->update([
                'status' => TransactionStatus::Reversed,
                'cancellation_reason' => $reason,
                'updated_by' => $user->id,
            ]);
            $this->log($original, $user, $request, 'reversed', 'Transaction reversed', [
                'reason' => $reason,
                'reversal_transaction_id' => $reversal->id,
            ]);

            return $reversal->refresh();
        });
    }

    public function delete(Transaction $transaction, User $user, Request $request): void
    {
        DB::transaction(function () use ($transaction, $user, $request): void {
            $locked = Transaction::query()->lockForUpdate()->findOrFail($transaction->id);
            $this->ensureStatus($locked, TransactionStatus::Draft);
            $this->log($locked, $user, $request, 'deleted', 'Draft transaction deleted');
            $locked->delete();
        });
    }

    private function applyBalanceEffect(Transaction $transaction, bool $inverse = false): void
    {
        $accountIds = collect([$transaction->financial_account_id, $transaction->destination_financial_account_id])
            ->filter()->unique()->sort()->values();
        $accounts = FinancialAccount::query()
            ->where('account_id', $transaction->account_id)
            ->whereIn('id', $accountIds)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $source = $accounts->get($transaction->financial_account_id);
        $destination = $transaction->destination_financial_account_id
            ? $accounts->get($transaction->destination_financial_account_id)
            : null;
        if (! $source || ($transaction->type === TransactionType::Transfer && ! $destination)) {
            throw ValidationException::withMessages(['financial_account_id' => 'Rekening transaksi tidak tersedia.']);
        }

        $factor = $inverse ? -1 : 1;
        $amount = (float) $transaction->amount;
        $fee = (float) $transaction->fee_amount;
        $sourceDelta = match ($transaction->type) {
            TransactionType::Income, TransactionType::Adjustment => $amount,
            TransactionType::Expense, TransactionType::Transfer => -($amount + $fee),
        };
        $source->update(['current_balance' => $this->money((float) $source->current_balance + ($sourceDelta * $factor))]);

        if ($transaction->type === TransactionType::Transfer && $destination) {
            $destination->update(['current_balance' => $this->money((float) $destination->current_balance + ($amount * $factor))]);
        }
    }

    /** @param array<string, mixed> $attributes */
    private function total(array $attributes): string
    {
        $amount = (float) $attributes['amount'];
        $fee = (float) ($attributes['fee_amount'] ?? 0);

        return $this->money($attributes['type'] === TransactionType::Income->value ? $amount : $amount + $fee);
    }

    /** @param array<string, mixed> $attributes */
    private function validateCategory(Account $account, array $attributes): void
    {
        if (empty($attributes['category_id'])) {
            return;
        }

        $category = TransactionCategory::query()
            ->where('account_id', $account->id)
            ->find($attributes['category_id']);
        $type = TransactionType::from($attributes['type']);
        if (! $category || ! in_array($category->type->value, [$type->value, 'both'], true)) {
            throw ValidationException::withMessages(['category_id' => 'Kategori tidak sesuai dengan jenis transaksi.']);
        }
    }

    /** @param array<int, UploadedFile> $attachments */
    private function storeAttachments(
        Transaction $transaction,
        array $attachments,
        User $user,
        TransactionSource $source = TransactionSource::Web,
    ): void {
        foreach ($attachments as $file) {
            $path = $file->store("transactions/{$transaction->account_id}/{$transaction->uuid}");
            $transaction->attachments()->create([
                'account_id' => $transaction->account_id,
                'file_name' => basename($path),
                'original_name' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                'size' => $file->getSize(),
                'source' => $source->value,
                'uploaded_by' => $user->id,
            ]);
        }
    }

    private function ensureStatus(Transaction $transaction, TransactionStatus $status): void
    {
        if ($transaction->status !== $status) {
            throw ValidationException::withMessages(['status' => "Transaksi harus berstatus {$status->label()}."]);
        }
    }

    private function ensureMonthlyQuota(Account $account): void
    {
        $subscription = $account->validSubscription()->with('plan')->first();
        $limit = $subscription?->plan?->max_monthly_transactions;
        if (! $limit) {
            return;
        }

        $used = $account->transactions()
            ->where('status', TransactionStatus::Posted->value)
            ->where('source', '!=', TransactionSource::System->value)
            ->whereBetween('transaction_date', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        if ($used >= $limit) {
            throw ValidationException::withMessages([
                'status' => "Kuota {$limit} transaksi bulanan pada paket aktif telah tercapai.",
            ]);
        }
    }

    private function nextNumber(): string
    {
        do {
            $number = 'TRX-'.now()->format('Ymd').'-'.strtoupper(Str::random(8));
        } while (Transaction::withTrashed()->where('transaction_number', $number)->exists());

        return $number;
    }

    private function money(float $value): string
    {
        return number_format($value, 2, '.', '');
    }

    /** @param array<string, mixed> $properties */
    private function log(Transaction $transaction, User $user, Request $request, string $event, string $description, array $properties = []): void
    {
        activity('transaction')
            ->causedBy($user)
            ->performedOn($transaction)
            ->event($event)
            ->withProperties($properties)
            ->tap(function (Activity $activity) use ($transaction, $request): void {
                $activity->account_id = $transaction->account_id;
                $activity->ip_address = $request->ip();
                $activity->user_agent = $request->userAgent();
            })
            ->log($description);
    }
}
