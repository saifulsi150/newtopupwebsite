<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AutoPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'package_name',
        'package_tagline',
        'provider_package_id',
        'provider',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function autoTopupOrders(): HasMany
    {
        return $this->hasMany(AutoTopupOrder::class);
    }
}
