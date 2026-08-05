<?php

namespace App\Http\Controllers;

use App\Constants\Status;
use App\Constants\OrderStatus;
use App\Library\UddoktaPay;
use App\Models\AutoPackage;
use App\Models\AutoTopupOrder;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Variation;
use App\Models\Voucher;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class OrdersController extends Controller
{
    // ============== TELEGRAM CONFIG ==============
    const TG_BOT_TOKEN = '7871654767:AAGgKAMasbsWBnAi5kL9VnR4tRg-J8yzA7M';
    const TG_CHAT_ID   = '-4741918127';
    private array $schemaColumnCache = [];
    private ?array $transactionColumnsCache = null;
    // =============================================

    public function buynow(Request $request)
    {
        $variation = Variation::where('stock', '>', 0)
            ->with(['product', 'vouchers' => function ($query) {
                $query->where('status', Status::AVAILABLE);
            }])
            ->findOrFail($request->variation_id);

        $quantity = max(1, (int) $request->input('quantity', 1));

        if ($variation->product->isVoucher() && $variation->vouchers->count() < $quantity) {
            return back()->with('error', __('Sorry, this voucher is out of stock.'));
        }

        $amount_cal = round($variation->price * $quantity, 2);
        $profit_cal = number_format(max(0, $amount_cal - ($variation->buy_rate * $quantity)), 2, '.', '');
        $accountInfo = $request->input('account_info');
        
        // Merge dynamic fields into account_info
        $dynamicFields = $request->input('dynamic_fields', []);
        if (is_array($dynamicFields) && !empty($dynamicFields)) {
            $accountInfo = is_array($accountInfo) ? $accountInfo : [];
            $accountInfo = array_merge($accountInfo, $dynamicFields);
        }

        $duplicateWindowSeconds = $this->resolveDuplicateWindowSeconds();
        $fingerprint = $this->buildDuplicateFingerprint(
            (int) Auth::id(),
            (int) $variation->id,
            $quantity,
            $accountInfo,
            (string) $request->payment_method
        );

        $resultCacheKey = 'order_result:' . $fingerprint;
        $lockKey = 'order_lock:' . $fingerprint;

        $cachedOrderId = (int) Cache::get($resultCacheKey, 0);
        if ($cachedOrderId > 0) {
            $cachedOrder = Order::with(['product'])->find($cachedOrderId);
            if ($cachedOrder) {
                $redirect = route('order.success', ['order' => $cachedOrder->id]);

                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'redirect_url' => $redirect,
                        'message' => 'Duplicate request detected. Redirecting to existing order.',
                    ]);
                }

                return redirect($redirect)->with('message', 'Duplicate request detected. Existing order opened.')->with('message_type', 'info');
            }
        }

        $duplicateOrder = $this->findRecentDuplicateOrder(
            (int) Auth::id(),
            (int) $variation->id,
            $quantity,
            $accountInfo,
            $duplicateWindowSeconds
        );

        if ($duplicateOrder) {
            $redirect = route('order.success', ['order' => $duplicateOrder->id]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'redirect_url' => $redirect,
                    'message' => 'Duplicate order detected. Redirecting to existing order.',
                ]);
            }

            return redirect($redirect)->with('message', 'Duplicate order detected. Existing order opened.')->with('message_type', 'info');
        }

        if (!Cache::add($lockKey, 1, now()->addSeconds($duplicateWindowSeconds))) {
            return $this->failResponse($request, __('Duplicate order request detected. Please wait a moment.'));
        }

        $orderData = $this->buildOrderData($variation, $quantity, $amount_cal, $profit_cal, $accountInfo, Auth::id());

        if (gs()->wallet && $request->payment_method === Status::WALLET) {
            try {
                $createdOrderId = null;
                $balanceBefore = null;
                $balanceAfter = null;
                $paymentMethodUsed = 'Wallet';

                DB::transaction(function () use ($orderData, $variation, $quantity, $amount_cal, &$createdOrderId, &$balanceBefore, &$balanceAfter) {
                    $user = User::whereKey(Auth::id())->lockForUpdate()->firstOrFail();

                    if ($amount_cal > $user->balance) {
                        throw new Exception(__('Insufficient Balance.'));
                    }

                    $balanceBefore = (float) $user->balance;

                    $vouchers = collect();
                    if ($variation->product->isVoucher()) {
                        $vouchers = Voucher::where('status', Status::AVAILABLE)
                            ->where('variation_id', $variation->id)
                            ->limit($quantity)
                            ->orderBy('id', 'DESC')
                            ->lockForUpdate()
                            ->get();

                        if ($vouchers->count() < $quantity) {
                            throw new Exception(__('Insufficient vouchers available.'));
                        }
                    }

                    $order = Order::create($orderData);
                    $this->ensureExternalRef($order);
                    $createdOrderId = $order->id;
                    $order->status = OrderStatus::PENDING;
                    $order->save();

                    $user->balance = $user->balance - $order->amount;
                    $user->save();
                    $balanceAfter = (float) $user->balance;

                    $this->createTransaction([
                        'user_id'        => $user->id,
                        'user_gmail'     => $user->email,
                        'method'         => 'Wallet',
                        'transaction_id' => 'WAL' . strtoupper(Str::random(12)),
                        'amount'         => $amount_cal,
                        'page'           => 'check out page',
                        'order_id'       => $order->id,
                        'time_paid'      => now(),
                        'unpaid'         => 0,
                    ]);

                    if ($order->product->isVoucher()) {
                        $order->variation->decrement('stock', $vouchers->count());

                        $voucherCodes = [];
                        foreach ($vouchers as $voucher) {
                            $voucherCodes[] = is_array($voucher->code) ? implode(',', $voucher->code) : $voucher->code;
                            $voucher->status = Status::SOLD;
                            $voucher->order_id = $order->id;
                            $voucher->save();
                        }

                        $order->voucher_code = implode(', ', $voucherCodes);
                        $order->save();
                    } else {
                        $order->variation->decrement('stock', $order->quantity);
                    }

                    $this->handleReseller($order);
                });

                if ($createdOrderId) {
                    Cache::put($resultCacheKey, $createdOrderId, now()->addSeconds($duplicateWindowSeconds));

                    $this->dispatchPostOrderTasks($createdOrderId, '🆕 New Order Placed (Wallet)', [
                        'payment_method' => 'Wallet',
                        'balance_before' => $balanceBefore,
                        'balance_after'  => $balanceAfter,
                    ]);
                }

                $redirect = route('order.success', ['order' => $createdOrderId]);
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => true, 'redirect_url' => $redirect, 'message' => 'Order Successful.']);
                }

                return redirect($redirect)->with('message', 'Order Successful.')->with('message_type', 'success');
            } catch (Exception $exception) {
                $this->sendNotification("⚠️ <b>Wallet Payment Failed!</b>\n👤 User: " . (Auth::user()->email ?? '-') . "\n❌ Error: " . $exception->getMessage());
                return $this->failResponse($request, $exception->getMessage());
            } finally {
                Cache::forget($lockKey);
            }
        }

        try {
            return $this->processUddoktaPay($variation, $orderData, $request);
        } finally {
            Cache::forget($lockKey);
        }
    }

    public function paymentSuccess(Request $request)
    {
        $transactionId = $request->query('transactionId') ?? $request->query('invoice_id');
        if (empty($transactionId)) {
            return redirect()->route('orders')->with('message', 'Order failed: Transaction ID missing.')->with('message_type', 'error');
        }

        $lockKey = 'gateway_order_lock:' . md5($transactionId);
        if (!Cache::add($lockKey, 1, now()->addSeconds(10))) {
            return redirect()->route('orders')->with('message', 'Order already processing.')->with('message_type', 'info');
        }

        try {
            $data = UddoktaPay::verify_payment($transactionId);
            if (!isset($data['status']) || $data['status'] !== 'COMPLETED') {
                return redirect()->route('orders')->with('message', 'Payment not completed.')->with('message_type', 'error');
            }

            $metadata = is_array($data['metadata']) ? $data['metadata'] : json_decode($data['metadata'], true);
            if (!$metadata || ($metadata['type'] ?? null) !== 'order') {
                return redirect()->route('orders')->with('message', 'Invalid metadata.')->with('message_type', 'error');
            }

            $user = Auth::user() ?? User::find($metadata['user_id']);
            if (!$user) {
                throw new Exception('User not found.');
            }

            $gatewayTrxId = $data['transaction_id'] ?? $transactionId;
            $paymentMethod = $data['payment_method'] ?? 'UddoktaPay';

            $orderId = DB::transaction(function () use ($metadata, $gatewayTrxId, $paymentMethod, $user) {
                $existingTransaction = Transaction::where('transaction_id', $gatewayTrxId)->first();
                if ($existingTransaction) {
                    return (int) ($existingTransaction->order_id ?? 0);
                }

                $variation = Variation::with('product')->whereKey($metadata['variation_id'])->lockForUpdate()->firstOrFail();
                $quantity = max(1, (int) ($metadata['quantity'] ?? 1));

                if ($variation->stock < $quantity) {
                    throw new Exception(__('Insufficient stock available.'));
                }

                $amount_cal = round($variation->price * $quantity, 2);
                $profit_cal = number_format(max(0, $amount_cal - ($variation->buy_rate * $quantity)), 2, '.', '');
                $orderData = $this->buildOrderData(
                    $variation,
                    $quantity,
                    $amount_cal,
                    $profit_cal,
                    $metadata['account_info'] ?? null,
                    $user->id,
                    OrderStatus::PENDING
                );

                $order = Order::create($orderData);
                $this->ensureExternalRef($order);

                // Gateway payments don't change wallet balance — pass current balance unchanged.
                $currentBalance = (float) ($user->balance ?? 0);

                $this->createTransaction([
                    'user_id'        => $user->id,
                    'user_gmail'     => $user->email,
                    'method'         => $paymentMethod,
                    'transaction_id' => $gatewayTrxId,
                    'amount'         => $amount_cal,
                    'page'           => 'check out page',
                    'order_id'       => $order->id,
                    'time_paid'      => now(),
                    'unpaid'         => 0,
                ]);

                if ($order->product->isVoucher()) {
                    $vouchers = Voucher::where('status', Status::AVAILABLE)
                        ->where('variation_id', $variation->id)
                        ->limit($order->quantity)
                        ->lockForUpdate()
                        ->get();

                    if ($vouchers->count() < $order->quantity) {
                        throw new Exception(__('Insufficient vouchers available.'));
                    }

                    $codes = [];
                    foreach ($vouchers as $voucher) {
                        $voucher->update(['status' => Status::SOLD, 'order_id' => $order->id]);
                        $codes[] = is_array($voucher->code) ? implode(',', $voucher->code) : $voucher->code;
                    }

                    $order->update(['voucher_code' => implode(', ', $codes)]);
                }

                $variation->decrement('stock', $order->quantity);

                $this->handleReseller($order);

                return (int) $order->id;
            });

            if ($orderId > 0) {
                $this->dispatchPostOrderTasks($orderId, "🆕 New Order Placed ({$paymentMethod})", [
                    'payment_method' => $paymentMethod,
                    'balance_before' => (float) ($user->balance ?? 0),
                    'balance_after'  => (float) ($user->balance ?? 0),
                ]);

                return redirect()->route('order.success', ['order' => $orderId])->with('message', 'Order Successful.')->with('message_type', 'success');
            }

            return redirect()->route('orders')->with('message', 'Order already processed.')->with('message_type', 'info');
        } catch (Exception $e) {
            $this->sendNotification("⚠️ <b>UddoktaPay Verification Failed!</b>\n❌ Error: " . $e->getMessage());
            return redirect()->route('orders')->with('message', 'Verification Error: ' . $e->getMessage())->with('message_type', 'error');
        }
    }

    private function processUddoktaPay($variation, $orderData, $request)
    {
        try {
            $user = Auth::user();
            
            // Prepare account info with dynamic fields
            $accountInfo = $request->input('account_info', []);
            $dynamicFields = $request->input('dynamic_fields', []);
            if (is_array($dynamicFields) && !empty($dynamicFields)) {
                $accountInfo = is_array($accountInfo) ? $accountInfo : [];
                $accountInfo = array_merge($accountInfo, $dynamicFields);
            }
            
            $requestData = [
                'full_name'    => $user->name ?? 'Guest User',
                'email'        => $user->email ?? 'no-email@test.com',
                'amount'       => $orderData['amount'],
                'metadata'     => [
                    'account_info' => $accountInfo,
                    'variation_id' => $variation->id,
                    'quantity'     => $request->input('quantity', 1),
                    'user_id'      => $user->id,
                    'type'         => 'order',
                ],
                'redirect_url' => route('payment.success'),
                'return_type'  => 'GET',
                'cancel_url'   => route('cancel.payment'),
            ];

            $paymentUrl = UddoktaPay::init_payment($requestData);
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'payment_url' => $paymentUrl]);
            }

            return redirect($paymentUrl);
        } catch (Exception $e) {
            $this->sendNotification("⚠️ <b>UddoktaPay Init Failed!</b>\n👤 User: " . (Auth::user()->email ?? '-') . "\n❌ Error: " . $e->getMessage());
            return $this->failResponse($request, $e->getMessage());
        }
    }

    private function buildOrderData($variation, int $quantity, $amount, $profit, $accountInfo, int $userId, $status = null): array
    {
        $accountInfoJson = is_array($accountInfo) ? json_encode($accountInfo, JSON_UNESCAPED_UNICODE) : $accountInfo;

        $data = [
            'user_id'      => $userId,
            'product_id'   => $variation->product->id,
            'variation_id' => $variation->id,
            'quantity'     => $quantity,
            'amount'       => $amount,
            'account_info' => $accountInfoJson,
        ];

        if ($status !== null) {
            $data['status'] = $status;
        }

        if ($this->hasColumnCached('orders', 'profit')) {
            $data['profit'] = $profit;
        }

        if ($this->hasColumnCached('orders', 'account_info_original')) {
            $data['account_info_original'] = $accountInfoJson;
        }

        if ($this->hasColumnCached('orders', 'account_info_to')) {
            $data['account_info_to'] = $accountInfoJson;
        }

        if ($this->hasColumnCached('orders', 'order_id_to')) {
            $data['order_id_to'] = $this->generateOrderIdTo();
        }

        return $data;
    }

    private function hasColumnCached(string $table, string $column): bool
    {
        if (!array_key_exists($table, $this->schemaColumnCache)) {
            $this->schemaColumnCache[$table] = array_flip(Schema::getColumnListing($table));
        }

        return array_key_exists($column, $this->schemaColumnCache[$table]);
    }

    private function getTransactionColumns(): array
    {
        if ($this->transactionColumnsCache === null) {
            $this->transactionColumnsCache = Schema::getColumnListing('transactions');
        }

        return $this->transactionColumnsCache;
    }

    private function generateOrderIdTo(): string
    {
        do {
            $uniqueId = 'ORD' . random_int(100000, 999999);
        } while (Order::where('order_id_to', $uniqueId)->exists());

        return $uniqueId;
    }

    private function ensureExternalRef(Order $order): void
    {
        if (!$this->hasColumnCached('orders', 'external_ref') || !empty($order->external_ref)) {
            return;
        }

        $order->external_ref = rtrim((string) config('app.url'), '/') . '#order-' . $order->id;
        $order->saveQuietly();
    }

    private function createTransaction(array $data): Transaction
    {
        foreach (['user_id', 'transaction_id', 'amount', 'order_id'] as $column) {
            if (!$this->hasColumnCached('transactions', $column)) {
                throw new Exception("transactions table missing {$column} column. Please run fix-order-create.sql first.");
            }
        }

        $columns = array_flip($this->getTransactionColumns());
        return Transaction::create(array_intersect_key($data, $columns));
    }

    private function failResponse(Request $request, string $message)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => false, 'message' => $message], 400);
        }

        return back()->with('message', $message)->with('message_type', 'error');
    }

    private function dispatchPostOrderTasks(int $orderId, string $title, array $context = []): void
    {
        $order = Order::with(['product', 'variation', 'user'])->find($orderId);

        if (!$order) {
            return;
        }

        $this->triggerAutomation($order, $context);
    }

    private function findRecentDuplicateOrder(int $userId, int $variationId, int $quantity, $accountInfo, int $windowSeconds): ?Order
    {
        $normalized = $this->normalizeAccountInfo($accountInfo);

        return Order::query()
            ->where('user_id', $userId)
            ->where('variation_id', $variationId)
            ->where('quantity', $quantity)
            ->whereIn('status', [
                OrderStatus::PENDING,
                OrderStatus::RUNNING,
                OrderStatus::LOOKING,
                OrderStatus::COMPLETE,
                Status::PROCESSING,
                Status::AUTOPROCESSING,
                Status::COMPLETE,
                Status::RUNNING,
                Status::LOOKING,
            ])
            ->where('created_at', '>=', now()->subSeconds($windowSeconds))
            ->where(function ($query) use ($normalized) {
                $query->where('account_info', $normalized)
                    ->orWhere('account_info', json_encode(['player_id' => $normalized], JSON_UNESCAPED_UNICODE));
            })
            ->with(['product'])
            ->latest('id')
            ->first();
    }

    private function resolveDuplicateWindowSeconds(): int
    {
        $seconds = (int) env('ORDER_DUPLICATE_WINDOW_SECONDS', 10);

        return max(3, min(30, $seconds));
    }

    private function normalizeAccountInfo($accountInfo): string
    {
        if (is_array($accountInfo)) {
            ksort($accountInfo);
            return json_encode($accountInfo, JSON_UNESCAPED_UNICODE);
        }

        return (string) $accountInfo;
    }

    private function buildDuplicateFingerprint(int $userId, int $variationId, int $quantity, $accountInfo, string $paymentMethod): string
    {
        return sha1(implode('|', [
            $userId,
            $variationId,
            $quantity,
            $this->normalizeAccountInfo($accountInfo),
            $paymentMethod,
        ]));
    }

    private function handleReseller(Order $order)
    {
        $user = $order->user;
        if ($user && method_exists($user, 'isReseller') && $user->isReseller()) {
            $percentageAmount = ($order->amount * $order->product->percentage) / 100;
            $user->increment('balance', $percentageAmount);
        }
    }

    private function triggerAutomation($order, array $context = [])
    {
        try {
            $order->loadMissing(['product','variation']);

            if ($order->product && method_exists($order->product,'isVoucher') && $order->product->isVoucher()) {
                return;
            }

            $tagline      = $order->variation->provider_product_id ?? null;
            $isTopup      = $order->product && method_exists($order->product,'isTopup') ? $order->product->isTopup() : false;
            $isAutomatic  = $order->variation && method_exists($order->variation,'isAutomatic') ? $order->variation->isAutomatic() : false;
            $autoEnabled  = (bool) (gs()->automation_enabled ?? true);
            $isProcessing = ($order->status === Status::PROCESSING);

            if (!empty($tagline) && $isAutomatic && $autoEnabled && $isProcessing) {
                $this->transferToNewApi($order, $context);
                return;
            }

            if ($isTopup && $isAutomatic && $autoEnabled && $isProcessing) {
                $this->transferToNewApi($order, $context);
                return;
            }

            $reasons = [];
            if (empty($tagline))   $reasons[] = 'provider_product_id (Auto Topup tagline) খালি';
            if (!$isTopup)         $reasons[] = 'product type ≠ topup';
            if (!$isAutomatic)     $reasons[] = 'variation isAutomatic = false';
            if (!$autoEnabled)     $reasons[] = 'Admin → Settings → automation_enabled OFF';
            if (!$isProcessing)    $reasons[] = 'order status ≠ processing (= ' . $order->status . ')';

            $this->notifyOrderEvent(
                $order,
                "📝 Auto-forward Skipped — Manual Action Needed\n🚫 কারণ: " . implode(' | ', $reasons),
                'warning',
                $context
            );
        } catch (Exception $e) {
            \Log::error('Automation Error: ' . $e->getMessage());
            $this->notifyOrderEvent($order, '❌ Automation Error: ' . $e->getMessage(), 'error', $context);
        }
    }

    private function transferToNewApi(Order $order, array $context = [])
    {
        $order->loadMissing(['product', 'variation', 'user']);

        $info = $order->account_info;
        if (is_string($info)) {
            $decoded = json_decode($info, true);
            if (is_array($decoded)) $info = $decoded;
        }
        $uid = is_array($info) ? ($info['player_id'] ?? ($info['uid'] ?? json_encode($info, JSON_UNESCAPED_UNICODE))) : $info;

        $settings = gs();
        $automationEnabled = (bool) ($settings->automation_enabled ?? true);
        $websiteApiUrl = rtrim((string) ($settings->website_api_url ?? ''), '/');
        $websiteApiKey = trim((string) ($settings->website_api_key ?? ''));
        $variationName = trim((string) ($order->variation->provider_product_id ?: $order->variation->title));
        $sourceSiteUrl = rtrim((string) config('app.url'), '/');
        $sourceSiteName = (string) ($settings->site_name ?? parse_url($sourceSiteUrl, PHP_URL_HOST) ?? 'Website');
        $callbackUrl = $sourceSiteUrl . '/api/tastnow/order-callback';

        if (!$automationEnabled) {
            $this->notifyOrderEvent(
                $order,
                '⚠️ Auto TopUp is disabled in Topup Server settings.',
                'warning',
                $context
            );
            return;
        }

        if ($websiteApiUrl === '' || $variationName === '') {
            $this->notifyOrderEvent(
                $order,
                '⚠️ Topup Server config incomplete. Website API URL or variation package name is missing.',
                'error',
                $context
            );
            return;
        }

        $payload = [
            'order_id' => (string) $order->id,
            'product_variation_name' => $variationName,
            'diamond_quantity' => $variationName,
            'uid' => (string) $uid,
            'status' => 'Processing',
            'order_time' => now()->toIso8601String(),
            'source_site_name' => $sourceSiteName,
            'source_site_url' => $sourceSiteUrl,
            'callback_url' => $callbackUrl,
            'source_balance_before' => (float) (($order->user?->balance ?? 0) + ($order->amount ?? 0)),
            'source_balance_after' => (float) ($order->user?->balance ?? 0),
            'source_balance_current' => (float) ($order->user?->balance ?? 0),
            'source_balance_deducted' => (float) ($order->amount ?? 0),
        ];

        if ($websiteApiKey !== '') {
            $payload['api_key'] = $websiteApiKey;
        }

        $attemptPaths = [
            '/webhook/website/order',
            '/webhook/order',
            '/webhook/order/api',
        ];

        $tracking = AutoTopupOrder::updateOrCreate(
            ['order_id' => $order->id],
            [
                'auto_package_id' => null,
                'provider' => 'website-webhook',
                'endpoint' => $websiteApiUrl,
                'forward_status' => 'pending',
                'request_payload' => $payload,
                'failure_reason' => null,
            ]
        );

        $lastErrorMessage = '';

        foreach ($attemptPaths as $path) {
            $endpoint = $websiteApiUrl . $path;

            try {
                $httpClient = Http::connectTimeout(4)
                    ->timeout(15)
                    ->withHeaders([
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                        'User-Agent' => 'tast.ffuid.shop/1.0',
                        'Authorization' => $websiteApiKey !== '' ? ('Bearer ' . $websiteApiKey) : '',
                        'x-api-key' => $websiteApiKey,
                    ]);

                $response = $httpClient->post($endpoint, $payload);

                $responseJson = $response->json();
                if (!is_array($responseJson)) {
                    $responseJson = ['raw' => substr((string) $response->body(), 0, 1500)];
                }

                $successFlag = (bool) ($responseJson['success'] ?? false);
                if ($response->successful() && $successFlag) {
                    $tracking->fill([
                        'endpoint' => $endpoint,
                        'forward_status' => 'forwarded',
                        'response_payload' => $responseJson,
                        'remote_order_id' => (string) ($responseJson['order_id'] ?? $order->id),
                        'remote_status' => (string) ($responseJson['status'] ?? 'processing'),
                        'forwarded_at' => now(),
                        'failure_reason' => null,
                    ])->save();

                    $order->update(['status' => OrderStatus::AUTOPROCESSING]);

                    $this->notifyOrderEvent(
                        $order,
                        "✅ Order Forwarded to Topup Server\n🆔 UID: <code>{$uid}</code>\n📦 Package: <code>{$variationName}</code>\n🔗 Endpoint: <code>{$endpoint}</code>",
                        'success',
                        $context
                    );
                    return;
                }

                $lastErrorMessage = (string) ($responseJson['message'] ?? ('HTTP ' . $response->status()));
            } catch (Exception $exception) {
                $lastErrorMessage = $exception->getMessage();

                if (str_contains($lastErrorMessage, 'cURL error 60') || str_contains($lastErrorMessage, 'SSL certificate')) {
                    try {
                        $response = $httpClient
                            ->withoutVerifying()
                            ->post($endpoint, $payload);

                        $responseJson = $response->json();
                        if (!is_array($responseJson)) {
                            $responseJson = ['raw' => substr((string) $response->body(), 0, 1500)];
                        }

                        $successFlag = (bool) ($responseJson['success'] ?? false);
                        if ($response->successful() && $successFlag) {
                            $tracking->fill([
                                'endpoint' => $endpoint,
                                'forward_status' => 'forwarded',
                                'response_payload' => $responseJson,
                                'remote_order_id' => (string) ($responseJson['order_id'] ?? $order->id),
                                'remote_status' => (string) ($responseJson['status'] ?? 'processing'),
                                'forwarded_at' => now(),
                                'failure_reason' => null,
                            ])->save();

                            $order->update(['status' => OrderStatus::AUTOPROCESSING]);

                            $this->notifyOrderEvent(
                                $order,
                                "✅ Order Forwarded to Topup Server (SSL fallback)\n🆔 UID: <code>{$uid}</code>\n📦 Package: <code>{$variationName}</code>\n🔗 Endpoint: <code>{$endpoint}</code>",
                                'success',
                                $context
                            );
                            return;
                        }

                        $lastErrorMessage = (string) ($responseJson['message'] ?? ('HTTP ' . $response->status()));
                    } catch (Exception $sslException) {
                        $lastErrorMessage = $sslException->getMessage();
                    }
                }
            }
        }

        $tracking->fill([
            'endpoint' => $websiteApiUrl,
            'forward_status' => 'failed',
            'response_payload' => ['message' => $lastErrorMessage],
            'forwarded_at' => now(),
            'failure_reason' => $lastErrorMessage !== '' ? $lastErrorMessage : 'All webhook endpoints failed',
        ])->save();

        $this->notifyOrderEvent(
            $order,
            "❌ Topup Server Transfer Failed\n🆔 UID: <code>{$uid}</code>\n📦 Package: <code>{$variationName}</code>\n📝 Error: " . ($lastErrorMessage !== '' ? $lastErrorMessage : 'All webhook endpoints failed'),
            'error',
            $context
        );

        return;

        $settings = gs();
        $resellerEndpoint = rtrim((string) ($settings->auto_topup_api_endpoint ?? ''), '/');
        $resellerApiKey = trim((string) ($settings->auto_topup_api_key ?? ''));
        $resellerUserId = trim((string) ($settings->auto_topup_user_id ?? ''));

        $tagline = trim((string) ($order->variation->provider_product_id ?? ''));
        $matchedPackage = null;

        if ($tagline !== '') {
            $matchedPackage = AutoPackage::query()
                ->where('provider', 'vnbazer')
                ->where('is_active', true)
                ->where('package_tagline', $tagline)
                ->first();
        }

        if (!$matchedPackage) {
            $matchedPackage = AutoPackage::query()
                ->where('provider', 'vnbazer')
                ->where('is_active', true)
                ->where('package_name', (string) ($order->variation->title ?? ''))
                ->first();
        }

        if ($tagline === '' && $matchedPackage) {
            $tagline = (string) $matchedPackage->package_tagline;
        }

        if ($resellerEndpoint !== '' && $resellerApiKey !== '' && $resellerUserId !== '' && $tagline !== '') {
            $quantity = (int) ($order->quantity ?? 1);
            $quantity = $quantity > 0 ? $quantity : 1;

            $accountInfoPayload = ['player_id' => (string) $uid];
            if (is_array($info)) {
                $accountInfoPayload = $info;

                if (!array_key_exists('player_id', $accountInfoPayload) && array_key_exists('uid', $accountInfoPayload)) {
                    $accountInfoPayload['player_id'] = (string) $accountInfoPayload['uid'];
                }

                if (array_key_exists('player_id', $accountInfoPayload)) {
                    $accountInfoPayload['player_id'] = (string) $accountInfoPayload['player_id'];
                }
            }

            $callbackUrl = route('auto.topup.webhook');
            $payload = [
                'user_id' => $resellerUserId,
                'account_info' => $accountInfoPayload,
                'selectedPackage' => [
                    'id' => (int) ($matchedPackage?->provider_package_id ?? 1),
                    'tag_line' => $tagline,
                ],
                'quantity' => $quantity,
                'order_id' => (string) $order->id,
                'url' => $callbackUrl,
            ];

            $tracking = AutoTopupOrder::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'auto_package_id' => $matchedPackage?->id,
                    'provider' => 'vnbazer',
                    'endpoint' => $resellerEndpoint,
                    'forward_status' => 'pending',
                    'request_payload' => $payload,
                    'failure_reason' => null,
                ]
            );

            try {
                $attemptEndpoints = [$resellerEndpoint];
                $parsedEndpointPath = (string) (parse_url($resellerEndpoint, PHP_URL_PATH) ?? '');
                if ($parsedEndpointPath === '/reseller-api') {
                    $attemptEndpoints[] = preg_replace('~/reseller-api$~', '/api/resaleorder', $resellerEndpoint);
                }

                $usedEndpoint = $resellerEndpoint;
                $response = null;
                $responseJson = null;

                foreach (array_unique(array_filter($attemptEndpoints)) as $candidateEndpoint) {
                    $usedEndpoint = $candidateEndpoint;

                    $response = Http::connectTimeout(3)
                        ->timeout(15)
                        ->withHeaders([
                            'Authorization' => 'Bearer ' . $resellerApiKey,
                            'Accept' => 'application/json',
                            'Content-Type' => 'application/json',
                        ])
                        ->post($candidateEndpoint, $payload);

                    $contentType = strtolower((string) $response->header('Content-Type', ''));
                    $responseJson = $response->json();

                    // Some VN Bazer routes can return storefront HTML instead of API JSON.
                    // In that case, retry once against /api/resaleorder.
                    if (
                        str_contains($contentType, 'text/html')
                        && $parsedEndpointPath === '/reseller-api'
                        && $candidateEndpoint !== end($attemptEndpoints)
                    ) {
                        continue;
                    }

                    break;
                }

                if (!is_array($responseJson)) {
                    $responseJson = ['raw' => substr((string) $response?->body(), 0, 2000)];
                }

                $message = strtolower((string) ($responseJson['message'] ?? ''));
                $isForwarded = $response->successful() && str_contains($message, 'order placed successfully');

                $tracking->fill([
                    'endpoint' => $usedEndpoint,
                    'forward_status' => $isForwarded ? 'forwarded' : 'failed',
                    'response_payload' => $responseJson,
                    'remote_order_id' => (string) ($responseJson['platform_order_id'] ?? $responseJson['id'] ?? ''),
                    'remote_status' => (string) ($responseJson['status'] ?? ''),
                    'forwarded_at' => now(),
                    'failure_reason' => $isForwarded ? null : ((string) ($responseJson['message'] ?? ('HTTP ' . ($response?->status() ?? 0)))),
                ])->save();

                if ($isForwarded) {
                    $order->update(['status' => OrderStatus::AUTOPROCESSING]);
                    $this->notifyOrderEvent(
                        $order,
                        "✅ Order Forwarded to VnBazer\n🆔 UID: <code>{$uid}</code>\n🏷️ Tagline: <code>{$tagline}</code>",
                        'success',
                        $context
                    );
                } else {
                    $this->notifyOrderEvent(
                        $order,
                        "❌ VnBazer Transfer Failed\n🆔 UID: <code>{$uid}</code>\n🏷️ Tagline: <code>{$tagline}</code>\n🔗 Endpoint: <code>{$usedEndpoint}</code>\n📝 Response: " . substr((string) ($responseJson['message'] ?? $response?->body()), 0, 400),
                        'error',
                        $context
                    );
                }
            } catch (Exception $e) {
                $tracking->fill([
                    'forward_status' => 'failed',
                    'forwarded_at' => now(),
                    'failure_reason' => $e->getMessage(),
                    'response_payload' => ['exception' => $e->getMessage()],
                ])->save();

                \Log::error('VnBazer Transfer Error: ' . $e->getMessage());
                $this->notifyOrderEvent($order, "⚠️ VnBazer Connection Error\n🆔 UID: <code>{$uid}</code>\n❌ " . $e->getMessage(), 'error', $context);
            }

            return;
        }

        $mainPanelUrl = rtrim((string) env('MAIN_PANEL_URL', ''), '/');
        $apiKey       = (string) env('MAIN_PANEL_API_KEY', '');
        $siteUrl      = rtrim((string) env('MAIN_PANEL_SITE_URL', config('app.url')), '/');

        if ($mainPanelUrl === '' || $apiKey === '') {
            $this->notifyOrderEvent(
                $order,
                "⚠️ Main Panel Forward Config Missing\nMAIN_PANEL_URL / MAIN_PANEL_API_KEY .env এ সেট করা নেই",
                'error',
                $context
            );
            return;
        }

        $apiUrl = preg_match('~/create$~', $mainPanelUrl) ? $mainPanelUrl : $mainPanelUrl . '/create';

        $packageName = $order->variation->provider_product_id
            ?? $order->variation->title
            ?? $order->product->name
            ?? '';

        // ===== User info + balance (before / after) =====
        $user            = $order->user;
        $userEmail       = $user->email ?? null;
        $userName        = $user->name ?? null;
        $paymentMethod   = $context['payment_method'] ?? null;
        $currentBalance  = (float) ($user->balance ?? 0);
        $balanceBefore   = array_key_exists('balance_before', $context) ? (float) $context['balance_before'] : $currentBalance;
        $balanceAfter    = array_key_exists('balance_after',  $context) ? (float) $context['balance_after']  : $currentBalance;

        $payload = [
            'external_order_id' => (string) $order->id,
            'product_name'      => $order->product->name ?? $packageName,
            'package_name'      => $packageName,
            'game_id'           => (string) $uid,
            'amount'            => (float) $order->amount,
            'callback_url'      => $siteUrl ? $siteUrl . '/api/tastnow/order-callback' : null,
            'account_info'      => $info,

            // 🆕 Customer info forwarded to main panel
            'user_email'        => $userEmail,
            'user_name'         => $userName,
            'customer_email'    => $userEmail,
            'customer_name'     => $userName,
            'payment_method'    => $paymentMethod,

            // 🆕 Wallet balance verification (before / after deduction)
            'user_balance'         => $currentBalance,
            'user_balance_before'  => $balanceBefore,
            'user_balance_after'   => $balanceAfter,
            'wallet_balance'       => $currentBalance,
            'wallet_before'        => $balanceBefore,
            'wallet_after'         => $balanceAfter,
            'balance_deducted'     => max(0, $balanceBefore - $balanceAfter),

            'customer' => [
                'email'          => $userEmail,
                'name'           => $userName,
                'balance'        => $currentBalance,
                'balance_before' => $balanceBefore,
                'balance_after'  => $balanceAfter,
                'payment_method' => $paymentMethod,
            ],
        ];

        try {
            $response = Http::connectTimeout(1)->timeout(2)->withHeaders([
                'x-api-key'    => $apiKey,
                'Referer'      => $siteUrl ?: url('/'),
                'Origin'       => $siteUrl ?: url('/'),
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ])->post($apiUrl, $payload);

            if ($response->successful()) {
                $order->update(['status' => OrderStatus::AUTOPROCESSING]);
                $this->notifyOrderEvent(
                    $order,
                    "✅ Order Forwarded to Main Panel Successfully\n🆔 UID: <code>{$uid}</code>\n🔗 URL: <code>{$apiUrl}</code>",
                    'success',
                    $context
                );
            } else {
                $this->notifyOrderEvent(
                    $order,
                    "❌ Main Panel Transfer FAILED\n🆔 UID: <code>{$uid}</code>\n🔗 URL: <code>{$apiUrl}</code>\n📡 HTTP: " . $response->status() . "\n📝 Response: " . substr($response->body(), 0, 500),
                    'error',
                    $context
                );
            }
        } catch (Exception $e) {
            \Log::error('Main Panel Transfer Error: ' . $e->getMessage());
            $this->notifyOrderEvent($order, "⚠️ Main Panel Connection Error\n🆔 UID: <code>{$uid}</code>\n🔗 URL: <code>{$apiUrl}</code>\n❌ " . $e->getMessage(), 'error', $context);
        }
    }

    /**
     * Telegram notification with package/customer/UID/balance details.
     */
    private function notifyOrderEvent(Order $order, string $title, string $kind = 'info', array $context = [])
    {
        try {
            $order->loadMissing(['user', 'product', 'variation']);

            $info = $order->account_info;
            if (is_string($info)) {
                $decoded = json_decode($info, true);
                if (is_array($decoded)) $info = $decoded;
            }
            if (is_array($info)) {
                $uidParts = [];
                foreach ($info as $k => $v) {
                    if (is_scalar($v) && $v !== '') $uidParts[] = "{$k}: {$v}";
                }
                $uidText = $uidParts ? implode(' | ', $uidParts) : '-';
            } else {
                $uidText = $info ?: '-';
            }

            $emoji = ['success' => '🟢', 'warning' => '🟡', 'error' => '🔴'][$kind] ?? '🔵';

            $currentBalance = (float) ($order->user->balance ?? 0);
            $balanceBefore  = array_key_exists('balance_before', $context) ? (float) $context['balance_before'] : null;
            $balanceAfter   = array_key_exists('balance_after',  $context) ? (float) $context['balance_after']  : null;
            $paymentMethod  = $context['payment_method'] ?? null;

            $msg  = "{$emoji} <b>{$title}</b>\n";
            $msg .= "━━━━━━━━━━━━━━━━\n";
            $msg .= "🧾 <b>Order ID:</b> #{$order->id}\n";
            $msg .= "📦 <b>Package:</b> " . ($order->variation->title ?? '-') . "\n";
            $msg .= "🛍️ <b>Product:</b> " . ($order->product->name ?? '-') . "\n";
            $msg .= "💵 <b>Amount:</b> " . number_format((float)$order->amount, 2) . "\n";
            $msg .= "📊 <b>Status:</b> " . ($order->status ?? '-') . "\n";
            if ($paymentMethod) {
                $msg .= "💳 <b>Payment:</b> {$paymentMethod}\n";
            }
            $msg .= "👤 <b>Customer:</b> " . ($order->user->name ?? '-') . "\n";
            $msg .= "📧 <b>Email:</b> " . ($order->user->email ?? '-') . "\n";
            if ($balanceBefore !== null && $balanceAfter !== null) {
                $msg .= "💰 <b>Balance Before:</b> " . number_format($balanceBefore, 2) . "\n";
                $msg .= "💰 <b>Balance After:</b> "  . number_format($balanceAfter, 2)  . "\n";
            } else {
                $msg .= "💰 <b>Current Balance:</b> " . number_format($currentBalance, 2) . "\n";
            }
            $msg .= "🆔 <b>UID / Account:</b> <code>" . htmlspecialchars($uidText, ENT_QUOTES) . "</code>\n";
            $msg .= "🕒 <b>Time:</b> " . now()->format('d M Y, h:i A');

            $this->sendNotification($msg);
        } catch (Exception $e) {
            \Log::error('notifyOrderEvent: ' . $e->getMessage());
        }
    }

    private function sendNotification($message)
    {
        try {
            $token = env('TELEGRAM_BOT_TOKEN');
            $chat  = env('TELEGRAM_CHAT_ID');

            if (!$token || !$chat) {
                try {
                    $settings = app(\App\Settings\GeneralSettings::class);
                    if (!empty($settings->botToken_1) && !empty($settings->chatId_1)) {
                        $token = $token ?: $settings->botToken_1;
                        $chat  = $chat  ?: $settings->chatId_1;
                    }
                } catch (Exception $e) { /* ignore */ }
            }

            $token = $token ?: self::TG_BOT_TOKEN;
            $chat  = $chat  ?: self::TG_CHAT_ID;

            if (!$token || !$chat) return;

            Http::connectTimeout(1)->timeout(1)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id'                  => $chat,
                'text'                     => $message,
                'parse_mode'               => 'HTML',
                'disable_web_page_preview' => true,
            ]);
        } catch (Exception $e) {
            \Log::error('Telegram Notify Error: ' . $e->getMessage());
        }
    }

    public function success($order)
    {
        $order = Order::with(['product', 'variation', 'transaction'])
            ->where('user_id', Auth::id())
            ->findOrFail($order);

        return view('pages.order-success', ['order' => $order]);
    }
}
