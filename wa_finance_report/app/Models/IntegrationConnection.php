<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationConnection extends Model
{
    protected $fillable = [
        'account_id', 'provider', 'name', 'status', 'credentials_encrypted',
        'settings', 'last_connected_at', 'last_error',
    ];

    protected $hidden = ['credentials_encrypted'];

    protected function casts(): array
    {
        return [
            'credentials_encrypted' => 'encrypted',
            'settings' => 'array',
            'last_connected_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
