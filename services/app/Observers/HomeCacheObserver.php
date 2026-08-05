<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class HomeCacheObserver
{
    public function saved(Model $model): void
    {
        $this->bumpVersion($model->getTable());
    }

    public function deleted(Model $model): void
    {
        $this->bumpVersion($model->getTable());
    }

    private function bumpVersion(string $table): void
    {
        $keys = match ($table) {
            'products' => 'home.products.version',
            'categories' => 'home.categories.version',
            'sliders' => 'home.sliders.version',
            'variations' => ['home.variations.version', 'home.products.version'],
            default => null,
        };

        if ($keys === null) {
            return;
        }

        foreach ((array) $keys as $key) {
            $currentVersion = (int) Cache::get($key, 0);
            Cache::forever($key, $currentVersion + 1);
        }
    }
}