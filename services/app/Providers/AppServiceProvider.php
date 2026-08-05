<?php

namespace App\Providers;

use App\Constants\Status;
use App\Models\Order;
use App\Models\ProductPackage;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Variation;
use App\Models\Categorie;
use App\Models\Slider;
use App\Observers\HomeCacheObserver;
use App\Services\PWAService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Blade;
use Illuminate\Database\Eloquent\Model;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $viewShare['settings'] = gs();
        view()->share($viewShare);

        // Register @PWA Blade directive
        Blade::directive('PWA', function () {
            return "<?php 
                \$pwaService = new \\App\\Services\\PWAService();
                \$config = \$pwaService->generate();
                echo view('pwa.meta', ['config' => \$config, 'settings' => gs()])->render(); 
            ?>";
        });

        if (app()->isProduction()) {
            URL::forceScheme('https');
        } else {
            URL::forceScheme('http');
            URL::forceRootUrl(config('app.url'));
        }

        Model::preventLazyLoading(! app()->isProduction());

        Product::observe(HomeCacheObserver::class);
        Categorie::observe(HomeCacheObserver::class);
        Slider::observe(HomeCacheObserver::class);
        Variation::observe(HomeCacheObserver::class);
    }
}