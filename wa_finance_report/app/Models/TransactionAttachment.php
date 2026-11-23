<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionAttachment extends Model
{
    protected $fillable = [
        'account_id', 'transaction_id', 'file_name', 'original_name', 'path',
        'mime_type', 'size', 'source', 'metadata', 'uploaded_by',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'size' => 'integer'];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
