<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class GamevaultApiController extends Controller
{
    public function health()
    {
        DB::select('SELECT 1');

        return response()->json([
            'status' => 'healthy',
            'database' => 'connected',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function games()
    {
        $query = DB::table('products as p')
            ->where('p.status', 1)
            ->orderBy('p.slot');

        if (Schema::hasTable('categories')) {
            $query->leftJoin('categories as c', 'c.id', '=', 'p.categorie_id')
                ->addSelect('p.*', 'c.title as category_title');
        } else {
            $query->addSelect('p.*', DB::raw("'' as category_title"));
        }

        $products = $query->get();
        $productIds = $products->pluck('id')->all();

        $variations = collect();
        if (!empty($productIds)) {
            $variations = DB::table('variations')
                ->whereIn('product_id', $productIds)
                ->where(function ($q) {
                    $q->whereNull('status')->orWhere('status', 1);
                })
                ->orderBy('price')
                ->get()
                ->groupBy('product_id');
        }

        $payload = $products->map(function ($product) use ($variations) {
            $gamePackages = $variations->get($product->id, collect())->map(function ($pkg) {
                return [
                    'id' => 'var_' . (string) $pkg->id,
                    'name' => (string) $pkg->name,
                    'price' => (float) $pkg->price,
                    'originalPrice' => $pkg->original_price !== null ? (float) $pkg->original_price : (float) $pkg->price,
                    'badge' => $pkg->badge ?: null,
                ];
            })->values();

            $categoryKey = ((string) ($product->type ?? 'IDCODE')) === 'VOUCHER' ? 'voucher-codes' : 'uid-topup';
            $categoryName = (string) ($product->category_title ?? '');
            if ($categoryName === '') {
                $categoryName = $categoryKey === 'voucher-codes' ? 'Voucher & Gift Codes' : 'Direct UID Topup';
            }

            $image = (string) ($product->image ?? '');
            $imageUrl = str_starts_with($image, 'http') ? $image : url('/uploads/' . ltrim($image, '/'));

            return [
                'id' => (string) ($product->slug ?: ('product-' . $product->id)),
                'name' => (string) ($product->title ?: 'Game'),
                'logo' => (string) $imageUrl,
                'banner' => (string) $imageUrl,
                'placeholder' => (string) (($product->input ?? '') ?: 'Enter Player ID (UID)'),
                'category' => $categoryKey,
                'categoryName' => (string) $categoryName,
                'uidCheckEnabled' => (int) ($product->uid_checker ?? 0) > 0,
                'uidCheckApiUrl' => '',
                'packages' => $gamePackages,
            ];
        })->values();

        return response()->json($payload);
    }

    public function saveGame(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'string', 'max:80'],
            'name' => ['required', 'string', 'max:120'],
            'logo' => ['nullable', 'string', 'max:500'],
            'banner' => ['nullable', 'string', 'max:500'],
            'placeholder' => ['nullable', 'string', 'max:150'],
            'category' => ['nullable', 'string', 'max:40'],
            'uidCheckEnabled' => ['nullable', 'boolean'],
            'uidCheckApiUrl' => ['nullable', 'string', 'max:1000'],
        ]);

        $categoryKey = (string) ($validated['category'] ?? 'uid-topup');
        $productType = $categoryKey === 'voucher-codes' ? 'VOUCHER' : 'IDCODE';

        $categoryId = null;
        if (Schema::hasTable('categories')) {
            $categoryTitle = (string) $request->input('categoryName', '');
            if ($categoryTitle !== '') {
                $existingCategory = DB::table('categories')->where('title', $categoryTitle)->first();
                if ($existingCategory) {
                    $categoryId = $existingCategory->id;
                }
            }
        }

        DB::table('products')->updateOrInsert(
            ['slug' => $validated['id']],
            [
                'title' => $validated['name'],
                'image' => (string) ($validated['logo'] ?? $validated['banner'] ?? ''),
                'input' => $validated['placeholder'] ?? 'Enter Player ID (UID)',
                'type' => $productType,
                'uid_checker' => (int) ((bool) ($validated['uidCheckEnabled'] ?? false) ? 1 : 0),
                'status' => 1,
                'categorie_id' => $categoryId,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return response()->json(['success' => true, 'message' => 'Game saved successfully']);
    }

    public function deleteGame(string $id)
    {
        DB::table('products')->where('slug', $id)->update([
            'status' => 0,
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Game deleted successfully']);
    }

    public function savePackage(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'string', 'max:80'],
            'game_id' => ['required', 'string', 'max:80'],
            'name' => ['required', 'string', 'max:120'],
            'price' => ['required', 'numeric', 'min:0'],
            'original_price' => ['nullable', 'numeric', 'min:0'],
            'badge' => ['nullable', 'string', 'max:40'],
        ]);

        $product = DB::table('products')->where('slug', $validated['game_id'])->first();
        if (!$product) {
            return response()->json(['error' => 'Product not found for this package'], 404);
        }

        $variationId = null;
        if (preg_match('/^var_(\d+)$/', (string) $validated['id'], $m)) {
            $variationId = (int) $m[1];
        }

        $payload = [
            'product_id' => $product->id,
            'title' => $validated['name'],
            'price' => (float) $validated['price'],
            'gift_coins' => 0,
            'stock' => 999999,
            'automatic' => 0,
            'provider' => null,
            'provider_product_id' => null,
            'status' => 1,
            'updated_at' => now(),
        ];

        if ($variationId) {
            DB::table('variations')->updateOrInsert(
                ['id' => $variationId],
                $payload + ['created_at' => now()]
            );
        } else {
            DB::table('variations')->insert($payload + ['created_at' => now()]);
        }

        return response()->json(['success' => true, 'message' => 'Package saved successfully']);
    }

    public function deletePackage(string $id)
    {
        $variationId = null;
        if (preg_match('/^var_(\d+)$/', (string) $id, $m)) {
            $variationId = (int) $m[1];
        }

        if ($variationId) {
            DB::table('variations')->where('id', $variationId)->delete();
        }

        return response()->json(['success' => true, 'message' => 'Package deleted successfully']);
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'max:100'],
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $userId = DB::table('users')->insertGetId([
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'phone' => $validated['phone'] ?? '',
            'password' => Hash::make($validated['password']),
            'user_type' => 'user',
            'status' => 1,
            'balance' => 1000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'uid' => (string) $userId,
            'email' => strtolower($validated['email']),
            'displayName' => $validated['name'],
            'phone' => (string) ($validated['phone'] ?? ''),
            'walletBalance' => 1000,
            'isAdmin' => false,
        ]);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:150'],
            'password' => ['required', 'string', 'max:100'],
        ]);

        $user = DB::table('users')
            ->where('email', strtolower($validated['email']))
            ->where('status', 1)
            ->first();

        if (!$user || !Hash::check($validated['password'], (string) $user->password)) {
            return response()->json(['error' => 'Invalid email or password'], 401);
        }

        return response()->json([
            'uid' => (string) $user->id,
            'email' => (string) $user->email,
            'displayName' => (string) ($user->name ?? 'Gamer'),
            'phone' => (string) ($user->phone ?? ''),
            'walletBalance' => (float) ($user->balance ?? 0),
            'isAdmin' => ((string) ($user->user_type ?? 'user')) === 'admin',
        ]);
    }

    public function deletePackageLegacy(string $id)
    {
        // Kept for backward compatibility if any stale route calls this method.
        return $this->deletePackage($id);
    }

    public function siteSettings()
    {
        $settings = DB::table('gv_site_settings')->where('id', 'global')->first();
        $legacyNotice = $this->readLegacySetting('notice_content');
        $legacyWhatsapp = $this->readLegacySetting('whatsapp_number');
        $legacyTelegram = $this->readLegacySetting('telegram_link');
        $legacyMarquee = $this->readLegacySetting('marquee_text');

        $banners = DB::table('gv_banners')->orderByDesc('active')->orderBy('title')->get();
        if (Schema::hasTable('sliders')) {
            $sliderBanners = DB::table('sliders')
                ->where('status', 1)
                ->orderBy('order_column')
                ->get()
                ->map(function ($s) {
                    $image = (string) ($s->image_url ?? '');
                    $imageUrl = str_starts_with($image, 'http') ? $image : url('/uploads/' . ltrim($image, '/'));

                    return (object) [
                        'id' => 'slider_' . $s->id,
                        'title' => '',
                        'subtitle' => '',
                        'url' => $imageUrl,
                        'active' => true,
                    ];
                });

            if ($sliderBanners->isNotEmpty()) {
                $banners = $sliderBanners;
            }
        }

        $vouchers = DB::table('gv_vouchers')->orderByDesc('status')->orderByDesc('discount')->get();

        return response()->json([
            'noticeBanner' => (string) ($legacyNotice ?: ($settings->notice_banner ?? '')),
            'marqueeText' => (string) (($settings->marquee_text ?? '') ?: $legacyMarquee),
            'bKashNumber' => $settings->bkash_number ?? '',
            'nagadNumber' => $settings->nagad_number ?? '',
            'rocketNumber' => $settings->rocket_number ?? '',
            'whatsappNumber' => (string) (($settings->whatsapp_number ?? '') ?: $legacyWhatsapp ?: '8801756515340'),
            'tutorialLink' => $settings->tutorial_link ?? '',
            'telegramLink' => (string) (($settings->telegram_link ?? '') ?: $legacyTelegram),
            'banners' => $banners->map(fn ($b) => [
                'id' => (string) $b->id,
                'title' => (string) ($b->title ?? ''),
                'subtitle' => (string) ($b->subtitle ?? ''),
                'url' => (string) ($b->url ?? ''),
                'active' => (bool) $b->active,
            ])->values(),
            'vouchers' => $vouchers->map(fn ($v) => [
                'id' => (string) $v->id,
                'code' => (string) $v->code,
                'discount' => (float) $v->discount,
                'maxUses' => (int) $v->max_uses,
                'used' => (int) $v->used,
                'status' => (string) $v->status,
            ])->values(),
        ]);
    }

    public function saveSiteSettings(Request $request)
    {
        DB::table('gv_site_settings')->updateOrInsert(
            ['id' => 'global'],
            [
                'notice_banner' => (string) $request->input('noticeBanner', ''),
                'marquee_text' => (string) $request->input('marqueeText', ''),
                'bkash_number' => (string) $request->input('bKashNumber', ''),
                'nagad_number' => (string) $request->input('nagadNumber', ''),
                'rocket_number' => (string) $request->input('rocketNumber', ''),
                'whatsapp_number' => (string) $request->input('whatsappNumber', ''),
                'tutorial_link' => (string) $request->input('tutorialLink', ''),
                'telegram_link' => (string) $request->input('telegramLink', ''),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $this->writeLegacySetting('notice_content', (string) $request->input('noticeBanner', ''));
        $this->writeLegacySetting('marquee_text', (string) $request->input('marqueeText', ''));
        $this->writeLegacySetting('whatsapp_number', (string) $request->input('whatsappNumber', ''));
        $this->writeLegacySetting('telegram_link', (string) $request->input('telegramLink', ''));

        $banners = $request->input('banners', []);
        if (is_array($banners)) {
            DB::table('gv_banners')->delete();

            foreach ($banners as $index => $banner) {
                $id = (string) ($banner['id'] ?? ('b_' . Str::uuid()));
                DB::table('gv_banners')->insert([
                    'id' => $id,
                    'title' => (string) ($banner['title'] ?? ''),
                    'subtitle' => (string) ($banner['subtitle'] ?? ''),
                    'url' => (string) ($banner['url'] ?? ''),
                    'active' => (bool) ($banner['active'] ?? true),
                    'created_at' => now()->addMilliseconds($index),
                    'updated_at' => now()->addMilliseconds($index),
                ]);
            }
        }

        $vouchers = $request->input('vouchers', []);
        if (is_array($vouchers)) {
            DB::table('gv_vouchers')->delete();

            foreach ($vouchers as $index => $voucher) {
                $id = (string) ($voucher['id'] ?? ('v_' . Str::uuid()));
                DB::table('gv_vouchers')->insert([
                    'id' => $id,
                    'code' => (string) ($voucher['code'] ?? $id),
                    'discount' => (float) ($voucher['discount'] ?? 0),
                    'max_uses' => (int) ($voucher['maxUses'] ?? 50),
                    'used' => (int) ($voucher['used'] ?? 0),
                    'status' => (string) ($voucher['status'] ?? 'active'),
                    'created_at' => now()->addMilliseconds($index),
                    'updated_at' => now()->addMilliseconds($index),
                ]);
            }
        }

        return response()->json(['success' => true, 'message' => 'Settings saved successfully']);
    }

    public function checkUid(Request $request)
    {
        $validated = $request->validate([
            'gameId' => ['required', 'string', 'max:80'],
            'uid' => ['required', 'string', 'max:120'],
        ]);

        $product = DB::table('products')->where('slug', $validated['gameId'])->first();
        if (!$product) {
            return response()->json(['valid' => false, 'message' => 'Game not found'], 404);
        }

        $checker = (int) ($product->uid_checker ?? 0);
        if ($checker <= 0) {
            return response()->json(['valid' => false, 'message' => 'UID check is disabled for this product'], 400);
        }

        $uid = (string) $validated['uid'];

        try {
            if ($checker === 1) {
                $response = Http::timeout(10)->acceptJson()->get(
                    'https://faas-sgp1-18bc02ac.doserverless.co/api/v1/web/fn-d48311ea-349c-4d0b-b4ea-bab4a937cbf8/default/FreeFire',
                    ['id' => $uid]
                );

                $body = $response->json();
                $message = is_array($body) && isset($body['message']) ? (string) $body['message'] : ($response->successful() ? 'Player verified' : 'UID INVALID');

                return response()->json([
                    'valid' => $response->successful(),
                    'message' => $message,
                ], $response->status());
            }

            $response = Http::timeout(10)->acceptJson()->get('http://203.18.158.131:5000/get', ['uid' => $uid]);
            $data = $response->json();
            $name = is_array($data) ? ($data['AccountInfo']['AccountName'] ?? null) : null;

            if ($response->successful() && $name) {
                return response()->json([
                    'valid' => true,
                    'message' => (string) $name,
                ]);
            }

            return response()->json([
                'valid' => false,
                'message' => 'UID INVALID',
            ], $response->status() ?: 400);
        } catch (\Throwable $e) {
            return response()->json([
                'valid' => false,
                'message' => 'UID API request failed',
            ], 502);
        }
    }

    public function user(string $uid)
    {
        $user = DB::table('users')->where('id', (int) $uid)->first();
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        return response()->json([
            'uid' => (string) $user->id,
            'email' => (string) $user->email,
            'displayName' => (string) ($user->name ?? 'Gamer'),
            'phone' => (string) ($user->phone ?? ''),
            'walletBalance' => (float) ($user->balance ?? 0),
            'joinedAt' => optional($user->created_at)->__toString() ?: now()->toIso8601String(),
            'isAdmin' => ((string) ($user->user_type ?? 'user')) === 'admin',
        ]);
    }

    public function saveUser(Request $request)
    {
        $validated = $request->validate([
            'uid' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150'],
            'name' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'walletBalance' => ['nullable', 'numeric'],
            'isAdmin' => ['nullable', 'boolean'],
        ]);

        $isNumericUid = ctype_digit((string) $validated['uid']);

        if ($isNumericUid) {
            DB::table('users')->where('id', (int) $validated['uid'])->update([
                'email' => strtolower($validated['email']),
                'name' => $validated['name'] ?? 'Gamer',
                'phone' => $validated['phone'] ?? '',
                'balance' => $validated['walletBalance'] ?? 0,
                'user_type' => (bool) ($validated['isAdmin'] ?? false) ? 'admin' : 'user',
                'updated_at' => now(),
            ]);
        } else {
            DB::table('users')->updateOrInsert(
                ['email' => strtolower($validated['email'])],
                [
                    'name' => $validated['name'] ?? 'Gamer',
                    'phone' => $validated['phone'] ?? '',
                    'balance' => $validated['walletBalance'] ?? 0,
                    'user_type' => (bool) ($validated['isAdmin'] ?? false) ? 'admin' : 'user',
                    'status' => 1,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        return response()->json(['success' => true, 'message' => 'User profile saved successfully']);
    }

    public function allUsers()
    {
        $users = DB::table('users')->orderByDesc('created_at')->get();

        return response()->json($users->map(fn ($u) => [
            'uid' => (string) $u->id,
            'name' => (string) ($u->name ?? ''),
            'email' => (string) $u->email,
            'phone' => (string) ($u->phone ?? ''),
            'walletBalance' => (float) ($u->balance ?? 0),
            'isAdmin' => ((string) ($u->user_type ?? 'user')) === 'admin',
            'created_at' => optional($u->created_at)->__toString(),
        ])->values());
    }

    public function transactions(Request $request)
    {
        $query = DB::table('gv_transactions')->orderByDesc('created_at');
        if ($request->filled('userId')) {
            $query->where('user_id', $request->string('userId'));
        }

        $items = $query->get();

        return response()->json($items->map(fn ($t) => [
            'id' => (string) $t->id,
            'userId' => (string) $t->user_id,
            'amount' => (float) $t->amount,
            'type' => (string) $t->type,
            'method' => (string) $t->method,
            'status' => (string) $t->status,
            'trxId' => $t->trx_id,
            'gameId' => $t->game_id,
            'packageName' => $t->package_name,
            'playerId' => $t->player_id,
            'timestamp' => (string) $t->timestamp,
        ])->values());
    }

    public function saveTransaction(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'string', 'max:120'],
            'userId' => ['required', 'string', 'max:150'],
            'amount' => ['required', 'numeric', 'min:0'],
            'type' => ['required', 'string', 'max:20'],
            'method' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', 'string', 'max:20'],
            'trxId' => ['nullable', 'string', 'max:120'],
            'gameId' => ['nullable', 'string', 'max:120'],
            'packageName' => ['nullable', 'string', 'max:150'],
            'playerId' => ['nullable', 'string', 'max:150'],
            'timestamp' => ['nullable', 'string', 'max:120'],
        ]);

        DB::table('gv_transactions')->updateOrInsert(
            ['id' => $validated['id']],
            [
                'user_id' => $validated['userId'],
                'amount' => $validated['amount'],
                'type' => $validated['type'],
                'method' => $validated['method'] ?? 'Wallet',
                'status' => $validated['status'] ?? 'pending',
                'trx_id' => $validated['trxId'] ?? null,
                'game_id' => $validated['gameId'] ?? null,
                'package_name' => $validated['packageName'] ?? null,
                'player_id' => $validated['playerId'] ?? null,
                'timestamp' => $validated['timestamp'] ?? now()->toIso8601String(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        if (($validated['status'] ?? 'pending') === 'success') {
            $delta = $validated['type'] === 'payment' ? -1 * (float) $validated['amount'] : (float) $validated['amount'];
            DB::table('users')->where('id', (int) $validated['userId'])->increment('balance', $delta);
        }

        return response()->json(['success' => true, 'message' => 'Transaction saved']);
    }

    public function comments()
    {
        $items = DB::table('gv_comments')->orderByDesc('created_at')->get();

        return response()->json($items->map(fn ($c) => [
            'id' => (string) $c->id,
            'username' => (string) ($c->username ?? ''),
            'email' => (string) ($c->email ?? ''),
            'product' => (string) ($c->product ?? ''),
            'text' => (string) ($c->text ?? ''),
            'rating' => (int) ($c->rating ?? 5),
            'status' => (string) ($c->status ?? 'pending'),
            'timestamp' => (string) ($c->timestamp ?? now()->toIso8601String()),
        ])->values());
    }

    public function saveComment(Request $request)
    {
        $validated = $request->validate([
            'id' => ['nullable', 'string', 'max:80'],
            'username' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'string', 'max:150'],
            'product' => ['nullable', 'string', 'max:150'],
            'text' => ['required', 'string'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'status' => ['nullable', 'string', 'max:20'],
            'timestamp' => ['nullable', 'string', 'max:120'],
        ]);

        $id = $validated['id'] ?? (string) Str::uuid();

        DB::table('gv_comments')->updateOrInsert(
            ['id' => $id],
            [
                'username' => $validated['username'],
                'email' => $validated['email'] ?? '',
                'product' => $validated['product'] ?? '',
                'text' => $validated['text'],
                'rating' => $validated['rating'] ?? 5,
                'status' => $validated['status'] ?? 'pending',
                'timestamp' => $validated['timestamp'] ?? now()->toIso8601String(),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return response()->json(['success' => true]);
    }

    public function approveComment(string $id)
    {
        DB::table('gv_comments')->where('id', $id)->update(['status' => 'approved', 'updated_at' => now()]);

        return response()->json(['success' => true]);
    }

    public function deleteComment(string $id)
    {
        DB::table('gv_comments')->where('id', $id)->delete();

        return response()->json(['success' => true]);
    }

    private function syncFromLaravelProducts(): void {}

    private function buildCategoryMetaBySlug(): array { return []; }

    private function readLegacySetting(string $name): string
    {
        if (!Schema::hasTable('settings')) {
            return '';
        }

        $payload = DB::table('settings')
            ->where('group', 'general')
            ->where('name', $name)
            ->value('payload');

        if ($payload === null) {
            return '';
        }

        $decoded = json_decode((string) $payload, true);
        if (json_last_error() === JSON_ERROR_NONE && is_string($decoded)) {
            return $decoded;
        }

        return trim((string) $payload, "\" ");
    }

    private function writeLegacySetting(string $name, string $value): void
    {
        if (!Schema::hasTable('settings')) {
            return;
        }

        DB::table('settings')->updateOrInsert(
            ['group' => 'general', 'name' => $name],
            [
                'payload' => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'locked' => 0,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}
