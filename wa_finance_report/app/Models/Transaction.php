<?php

namespace App\Models;

use App\Enums\TransactionSource;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid', 'account_id', 'transaction_number', 'type', 'transaction_date',
        'financial_account_id', 'destination_financial_account_id', 'category_id',
        'amount', 'fee_amount', 'total_amount', 'currency', 'exchange_rate',
        'description', 'merchant', 'reference_number', 'notes', 'payment_method',
        'status', 'source', 'external_id', 'idempotency_key',
        'reversed_transaction_id', 'cancellation_reason',
        'created_by', 'updated_by', 'approved_by', 'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'status' => TransactionStatus::class,
            'source' => TransactionSource::class,
            'transaction_date' => 'datetime',
            'amount' => 'decimal:2',
            'fee_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'exchange_rate' => 'decimal:8',
            'approved_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function financialAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class);
    }

    public function destinationFinancialAccount(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'destination_financial_account_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TransactionCategory::class, 'category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function reversedTransaction(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversed_transaction_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TransactionAttachment::class);
    }
}
