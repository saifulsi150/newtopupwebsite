<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ProductPackage extends Model
{
    protected $table = 'product_packages';

    protected $fillable = [
        'product_id',
        'external_id',
        'name',
        'price',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (ProductPackage $package): void {
            if (empty($package->sync_uid)) {
                $package->sync_uid = (string) Str::uuid();
            }
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}