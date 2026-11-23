<?php

namespace App\Services;

use App\Enums\TransactionSource;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\AiProcessingLog;
use App\Models\FinancialAccount;
use App\Models\TransactionCategory;
use App\Models\User;
use App\Models\WebhookLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class IntegrationTransactionService
{
    public function __construct(private TransactionService $transactions) {}

    /** @param array<string, mixed> $payload
     * @return array{data: array<string, mixed>, status: int}
     */
    public function process(Account $account, User $user, array $payload, Request $request): array
    {
        $existing = WebhookLog::query()
            ->where('account_id', $account->id)
            ->where('idempotency_key', $payload['idempotency_key'])
            ->first();
        if ($existing) {
            return [
                'data' => [...($existing->response ?? []), 'duplicate' => true],
                'status' => $existing->status === 'needs_review' ? 202 : 200,
            ];
        }

        $webhook = $account->webhookLogs()->create([
            'provider' => $payload['source'],
            'event_name' => 'transaction.extracted',
            'external_id' => $payload['external_id'],
            'idempotency_key' => $payload['idempotency_key'],
            'headers' => $this->sanitizedHeaders($request),
            'payload' => $this->sanitizedPayload($payload),
            'status' => 'received',
            'received_at' => now(),
        ]);
        $aiLog = $account->aiProcessingLogs()->create([
            'source_type' => $payload['source'],
            'source_id' => $payload['external_id'],
            'input_type' => $payload['input_type'],
            'raw_input' => [
                'external_id' => $payload['external_id'],
                'sender' => $this->sanitizedSender($payload['sender'] ?? []),
                'ai_raw_result' => data_get($payload, 'ai.raw_result'),
            ],
            'extracted_data' => $payload['transaction'],
            'confidence_score' => data_get($payload, 'ai.confidence_score'),
            'model' => data_get($payload, 'ai.model'),
            'status' => 'processing',
        ]);

        try {
            $mapping = $this->resolveMapping($account, $payload['transaction']);
            if ($mapping['missing'] !== []) {
                $response = [
                    'status' => 'needs_review',
                    'external_id' => $payload['external_id'],
                    'missing_fields' => $mapping['missing'],
                    'review_id' => $aiLog->id,
                ];
                $this->completeLogs($webhook, $aiLog, $response, 'needs_review');

                return ['data' => $response, 'status' => 202];
            }

            $attributes = [
                'type' => $payload['transaction']['type'],
                'transaction_date' => $payload['transaction']['transaction_date'],
                'financial_account_id' => $mapping['financial_account']->id,
                'destination_financial_account_id' => $mapping['destination_account']?->id,
                'category_id' => $mapping['category']?->id,
                'amount' => $payload['transaction']['amount'],
                'fee_amount' => $payload['transaction']['fee_amount'] ?? 0,
                'description' => $payload['transaction']['description'],
                'merchant' => $payload['transaction']['merchant'] ?? null,
                'reference_number' => $payload['transaction']['reference_number'] ?? null,
                'payment_method' => $payload['transaction']['payment_method'] ?? null,
                'notes' => $payload['transaction']['notes'] ?? null,
                'status' => $payload['transaction']['status'] ?? TransactionStatus::Draft->value,
                'external_id' => $payload['external_id'],
                'idempotency_key' => $payload['idempotency_key'],
            ];
            $transaction = $this->transactions->create(
                $account,
                $user,
                $attributes,
                $request,
                TransactionSource::from($payload['source']),
            );
            $response = [
                'status' => 'completed',
                'external_id' => $payload['external_id'],
                'transaction_id' => $transaction->id,
                'transaction_uuid' => $transaction->uuid,
                'transaction_number' => $transaction->transaction_number,
                'transaction_status' => $transaction->status->value,
            ];
            $this->completeLogs($webhook, $aiLog, $response, 'completed');

            return ['data' => $response, 'status' => 201];
        } catch (Throwable $exception) {
            $webhook->update([
                'status' => 'failed',
                'error_message' => Str::limit($exception->getMessage(), 1000),
                'processed_at' => now(),
            ]);
            $aiLog->update([
                'status' => 'failed',
                'error_message' => Str::limit($exception->getMessage(), 1000),
                'processed_at' => now(),
            ]);

            throw $exception;
        }
    }

    /** @param array<string, mixed> $transaction */
    private function resolveMapping(Account $account, array $transaction): array
    {
        $financialAccount = $this->findAccount($account, $transaction['financial_account']);
        $destination = filled($transaction['destination_financial_account'] ?? null)
            ? $this->findAccount($account, $transaction['destination_financial_account'])
            : null;
        $category = filled($transaction['category'] ?? null)
            ? $this->findCategory($account, $transaction['category'])
            : null;
        $missing = [];
        if (! $financialAccount) {
            $missing[] = 'transaction.financial_account';
        }
        if (($transaction['type'] ?? null) === TransactionType::Transfer->value && ! $destination) {
            $missing[] = 'transaction.destination_financial_account';
        }
        if (filled($transaction['category'] ?? null) && ! $category) {
            $missing[] = 'transaction.category';
        }

        return [
            'financial_account' => $financialAccount,
            'destination_account' => $destination,
            'category' => $category,
            'missing' => $missing,
        ];
    }

    private function findAccount(Account $account, string $name): ?FinancialAccount
    {
        return $account->financialAccounts()
            ->where('is_active', true)
            ->whereRaw('LOWER(name) = ?', [Str::lower(trim($name))])
            ->first();
    }

    private function findCategory(Account $account, string $name): ?TransactionCategory
    {
        return $account->transactionCategories()
            ->where('is_active', true)
            ->whereRaw('LOWER(name) = ?', [Str::lower(trim($name))])
            ->first();
    }

    private function completeLogs(WebhookLog $webhook, AiProcessingLog $aiLog, array $response, string $status): void
    {
        DB::transaction(function () use ($webhook, $aiLog, $response, $status): void {
            $webhook->update(['response' => $response, 'status' => $status, 'processed_at' => now()]);
            $aiLog->update(['status' => $status, 'processed_at' => now()]);
        });
    }

    /** @return array<string, mixed> */
    private function sanitizedHeaders(Request $request): array
    {
        return collect($request->headers->all())
            ->except(['authorization', 'cookie', 'x-xsrf-token', 'x-csrf-token'])
            ->map(fn (array $values) => array_slice($values, 0, 5))
            ->all();
    }

    /** @param array<string, mixed> $payload */
    private function sanitizedPayload(array $payload): array
    {
        return [
            ...$payload,
            'sender' => $this->sanitizedSender($payload['sender'] ?? []),
            'ai' => isset($payload['ai']) ? [
                'model' => $payload['ai']['model'] ?? null,
                'confidence_score' => $payload['ai']['confidence_score'] ?? null,
                'raw_result' => '[stored in ai processing log]',
            ] : null,
        ];
    }

    /** @param array<string, mixed> $sender */
    private function sanitizedSender(array $sender): array
    {
        $phone = $sender['phone'] ?? null;

        return [
            'name' => $sender['name'] ?? null,
            'phone' => $phone ? Str::mask($phone, '*', 3, max(mb_strlen($phone) - 6, 1)) : null,
        ];
    }
}
