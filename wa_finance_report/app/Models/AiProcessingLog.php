<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiProcessingLog extends Model
{
    protected $fillable = [
        'account_id', 'source_type', 'source_id', 'input_type', 'raw_input',
        'extracted_data', 'confidence_score', 'model', 'status',
        'error_message', 'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'raw_input' => 'array',
            'extracted_data' => 'array',
            'confidence_score' => 'decimal:4',
            'processed_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
