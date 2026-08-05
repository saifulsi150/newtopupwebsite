<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active'      => 'boolean',
        'status'         => 'boolean',
        'has_tutorial'   => 'boolean',
        'uid_checker'    => 'integer',
        'slot'           => 'integer',
        'dynamic_fields' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (Product $product): void {
            if (empty($product->sync_uid)) {
                $product->sync_uid = (string) Str::uuid();
            }
        });
    }

    // ─── Type helpers (DB values are UPPERCASE) ───────────────────

    public function isVoucher(): bool
    {
        return $this->type === 'VOUCHER';
    }

    public function isTopup(): bool
    {
        return $this->type === 'IDCODE';
    }

    // Alias — blade may call isInGame() or isSubscription() too
    public function isInGame(): bool
    {
        return $this->type === 'INGAME';
    }

    public function isSubscription(): bool
    {
        return $this->type === 'SUBSCRIPTION';
    }

    // ─── Relationships ────────────────────────────────────────────

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(Categorie::class);
    }

    public function variations(): HasMany
    {
        return $this->hasMany(Variation::class)
            ->orderBy('slot')
            ->orderBy('id');
    }

    public function packages(): HasMany
    {
        return $this->hasMany(ProductPackage::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
