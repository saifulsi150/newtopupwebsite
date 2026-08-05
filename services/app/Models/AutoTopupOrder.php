<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutoTopupOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'auto_package_id',
        'provider',
        'endpoint',
        'forward_status',
        'remote_status',
        'remote_order_id',
        'forwarded_at',
        'callback_received_at',
        'request_payload',
        'response_payload',
        'callback_payload',
        'failure_reason',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
        'callback_payload' => 'array',
        'forwarded_at' => 'datetime',
        'callback_received_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function autoPackage(): BelongsTo
    {
        return $this->belongsTo(AutoPackage::class);
    }
}
