<?php

namespace App\Http\Controllers;

use App\Models\Categorie;
use App\Models\Product;
use App\Models\Slider;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class InertiaController extends Controller
{
    public function home()
    {
        $categorys = Categorie::where('status', 1)->orderBy('slot', 'asc')->get();
        $products = Product::where('status', 1)->orderBy('slot', 'asc')->get();
        $sliders = Slider::where('status', 1)->orderBy('order_column', 'asc')->get();

        return Inertia::render('Home', [
            'settings' => gs()->toArray(),
            'auth' => [
                'user' => Auth::check() ? Auth::user()->only(['id', 'name', 'email', 'balance', 'picture']) : null,
            ],
            'categorys' => $categorys,
            'sliders' => $sliders,
            'productsByCategory' => $products->groupBy('categorie_id')->map(fn ($items) => $items->values()->all())->toArray(),
        ]);
    }

    public function topup($slug)
    {
        $product = Product::with(['variations'])
            ->where('status', 1)
            ->where('slug', $slug)
            ->firstOrFail();

        return Inertia::render('Topup', [
            'settings' => gs()->toArray(),
            'auth' => [
                'user' => Auth::check() ? Auth::user()->only(['id', 'name', 'email', 'balance', 'picture']) : null,
            ],
            'product' => $product->toArray(),
        ]);
    }
}
