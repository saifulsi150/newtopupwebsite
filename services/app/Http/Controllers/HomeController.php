<?php

namespace App\Http\Controllers;

use App\Constants\Status;
use App\Models\Order;
use App\Models\Slider;
use App\Models\Categorie;
use App\Filters\OrderFilter;
use App\Models\Product;
use App\Models\Popup;
use App\Models\User;
use App\Models\Variation;
use App\Models\PlayerName;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class HomeController extends Controller
{
    public function home() {
    $categoryVersion = (int) Cache::get('home.categories.version', 0);
    $productVersion = (int) Cache::get('home.products.version', 0);
    $sliderVersion = (int) Cache::get('home.sliders.version', 0);

      try {
          $categorys = Cache::remember('home.categories.' . $categoryVersion, now()->addMinutes(10), function () {
              return Categorie::where('status', 1)->orderBy('slot', 'asc')->get();
          });
      } catch (\Exception $e) {
          $categorys = collect();
      }

      try {
          $products = Cache::remember('home.products.' . $productVersion, now()->addMinutes(10), function () {
              return Product::where('status', 1)->orderBy('slot', 'asc')->get();
          });
      } catch (\Exception $e) {
          $products = collect();
      }

      try {
          $sliders = Cache::remember('home.sliders.' . $sliderVersion, now()->addMinutes(10), function () {
              return Slider::where('status', 1)->orderBy('order_column', 'asc')->get();
          });
      } catch (\Exception $e) {
          $sliders = collect();
      }

      $productsByCategory = $products->groupBy('categorie_id');

      $pageCacheKey = 'home.page.' . $categoryVersion . '.' . $productVersion . '.' . $sliderVersion;
      $html = Cache::remember($pageCacheKey, now()->addMinutes(10), function () use ($categorys, $products, $productsByCategory, $sliders) {
          return view('pages.home', [
              'categorys' => $categorys,
              'products' => $products,
              'productsByCategory' => $productsByCategory,
              'sliders' => $sliders,
          ])->render();
      });

      return response($html, 200)
          ->header('Cache-Control', 'public, max-age=600, s-maxage=600')
          ->header('X-Page-Cache', 'home');
    }
    
    public function topup($slug) {
        $productVersion = (int) Cache::get('home.products.version', 0);
        $variationVersion = (int) Cache::get('home.variations.version', 0);

        $product = Cache::remember(
            'topup.product.' . $slug . '.' . $productVersion . '.' . $variationVersion,
            now()->addMinutes(5),
            function () use ($slug) {
                return Product::with(['variations'])
                    ->where('status', 1)
                    ->where('slug', $slug)
                    ->first();
            }
        );

        if (!$product) {
            abort(404);
        }

        $pageCacheKey = 'topup.page.' . $slug . '.' . $productVersion . '.' . $variationVersion;
        $html = Cache::remember($pageCacheKey, now()->addMinutes(5), function () use ($product) {
            return view('pages.topup', [
                'product' => $product,
            ])->render();
        });

        return response($html, 200)
            ->header('Cache-Control', 'public, max-age=300, s-maxage=300')
            ->header('X-Page-Cache', 'topup');
    }

    public function orders(Request $request) {
      $queryParams = $request->query();
      $queryBuilder = Order::with(['variation', 'product', 'voucher'])
          ->where('user_id', user_id())
          ->latest();

      $queryBuilder->whereDoesntHave('product', function (Builder $query) {
          $query->where('type', Status::VOUCHER);
      });

      $orders = app(OrderFilter::class)->getResults([
          'builder' => $queryBuilder,
          'params'  => $queryParams,
      ]);
      
      return view('pages.orders', [
        'orders' => $orders
      ]);
    }
    
    public function codes(Request $request) {
      $queryParams = $request->query();
      $queryBuilder = Order::with(['variation', 'product', 'voucher'])
          ->where('user_id', user_id())
          ->latest();

      $queryBuilder->whereHas('product', function (Builder $query) {
          $query->where('type', Status::VOUCHER);
      });

      $orders = app(OrderFilter::class)->getResults([
          'builder' => $queryBuilder,
          'params'  => $queryParams,
      ]);
      
      return view('pages.codes', [
        'codes' => $orders
      ]);
    }
    
    public function addfunds() {
      return view('pages.add-funds');
    }
    
    public function getPopups(Collection $collection, Request $request)
    {
        $popupQuery = Popup::query();
        if (!$request->session()->has('first_visit_popup')) {
            $firstVisitPopups = $popupQuery->where('type', Status::ONCE)
                ->where('status', Status::ACTIVE)
                ->get();
            $collection = $collection->merge($firstVisitPopups);
            $request->session()->put('first_visit_popup', true);
        }

        if (!$request->cookie('daily_popup_showed')) {
            $dailyOncePopups = $popupQuery->where('type', Status::DAILY)
                ->where('status', Status::ACTIVE)
                ->get();
            $collection = $collection->merge($dailyOncePopups);
            return response()->json(['popups' => $collection])->withCookie(cookie('daily_popup_showed', true, 1440));
        }

        return response()->json(['popups' => $collection]);
    }

    public function checkPlayerName(Request $request)
    {
        $request->validate([
            'uid' => 'required|string'
        ]);

        $uid = $request->input('uid');
        
        // Check database for existing record
        $playerName = PlayerName::where('uid', $uid)->first();
        
        // Check if data exists and is less than 5 days old
        if ($playerName && $playerName->data_updated_at) {
            $dataAgeInDays = Carbon::parse($playerName->data_updated_at)->diffInDays(Carbon::now());
            
            if ($dataAgeInDays < 5) {
                // Return cached data (less than 5 days old)
                return response()->json([
                    'success' => true,
                    'name' => $playerName->name,
                    'cached' => true
                ]);
            }
        }

        // Data is older than 5 days or doesn't exist, call API
        try {
            $response = Http::timeout(10)->get('http://203.18.158.131:5000/get', [
                'uid' => $uid
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $name = $data['AccountInfo']['AccountName'] ?? null;

                if ($name) {
                    // Update or create database record
                    PlayerName::updateOrCreate(
                        ['uid' => $uid],
                        [
                            'name' => $name,
                            'data_updated_at' => Carbon::now()
                        ]
                    );

                    return response()->json([
                        'success' => true,
                        'name' => $name,
                        'cached' => false
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Player name not found in API response'
                    ], 404);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch player data from API'
                ], $response->status());
            }
        } catch (Exception $e) {
            // If API fails but we have cached data, return cached data
            if ($playerName) {
                return response()->json([
                    'success' => true,
                    'name' => $playerName->name,
                    'cached' => true
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error checking player name: ' . $e->getMessage()
            ], 500);
        }
    }
}
