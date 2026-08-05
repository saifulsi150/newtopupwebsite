<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncLog extends Model
{
    protected $fillable = [
        'direction',
        'entity',
        'record_key',
        'source_site_url',
        'target_site_url',
        'status',
        'http_status',
        'message',
    ];
}
