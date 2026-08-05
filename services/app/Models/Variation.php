<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Variation extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'title',
        'price',
        'gift_coins',
        'stock',
        'slot',
        'automatic',
        'provider',
        'provider_product_id',
    ];

    protected $casts = [
        'slot'      => 'integer',
        'automatic' => 'boolean',
        'status'    => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Variation $variation): void {
            if (empty($variation->sync_uid)) {
                $variation->sync_uid = (string) Str::uuid();
            }

            if (empty($variation->slot)) {
                $variation->slot = (int) static::query()
                    ->where('product_id', $variation->product_id)
                    ->max('slot') + 1;
            }
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }

    public function isAutomatic(): bool
    {
        return $this->automatic;
    }

    public function providerType(Order $order)
    {
        $provider = "App\\Services\\TopupProvider\\{$this->provider}";
        return new $provider($order);
    }
}
