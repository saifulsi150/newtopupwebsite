<?php
namespace App\Http\Controllers\Api;

use App\Constants\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductPackage;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Throwable;

class TastnowSyncController extends Controller
{
    public function syncProduct(Request $request)
    {
        $data = $request->validate([
            'external_id' => 'required|string',
            'name'        => 'required|string',
            'category'    => 'nullable|string',
            'image_url'   => 'nullable|string',
            'is_active'   => 'boolean',
            'description' => 'nullable|string',
            'packages'    => 'array',
            'packages.*.external_id' => 'required|string',
            'packages.*.name'        => 'required|string',
            'packages.*.price'       => 'required|numeric',
            'packages.*.is_active'   => 'boolean',
        ]);

        $product = Product::updateOrCreate(
            ['external_id' => $data['external_id']],
            [
                'name' => $data['name'], 'category' => $data['category'] ?? null,
                'image_url' => $data['image_url'] ?? null, 'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'] ?? true, 'source' => 'main_panel',
            ]
        );
        foreach (($data['packages'] ?? []) as $pkg) {
            ProductPackage::updateOrCreate(
                ['external_id' => $pkg['external_id']],
                ['product_id' => $product->id, 'name' => $pkg['name'], 'price' => $pkg['price'], 'is_active' => $pkg['is_active'] ?? true]
            );
        }
        return response()->json(['ok' => true, 'product_id' => $product->id]);
    }

    /**
     * Receive order status + UID update from main panel.
     * Accepts many ID aliases and any status synonym. Updates UID into all
     * plain + JSON columns. Never returns HTML on error.
     */
    public function orderCallback(Request $request)
    {
        try {
            $candidates = array_values(array_filter([
                $request->input('external_order_id'),
                $request->input('external_ref'),
                $request->input('order_id'),
                $request->input('main_order_id'),
                $request->input('main_panel_order_id'),
                $request->input('source_order_id'),
                $request->input('website_order_id'),
                $request->input('api_order_id'),
            ]));
            if (empty($candidates)) {
                return response()->json(['ok' => false, 'error' => 'order id required'], 422);
            }

            $hasExtRef = Schema::hasColumn('orders', 'external_ref');
            $hasOrderId = Schema::hasColumn('orders', 'order_id');

            $order = Order::query()->where(function ($q) use ($candidates, $hasExtRef, $hasOrderId) {
                foreach ($candidates as $v) {
                    $q->orWhere('id', $v);
                    if ($hasExtRef)  $q->orWhere('external_ref', $v);
                    if ($hasOrderId) $q->orWhere('order_id', $v);
                }
            })->first();

            if (!$order) {
                Log::warning('tastnow callback: order not found', ['tried' => $candidates]);
                return response()->json(['ok' => false, 'error' => 'order not found', 'tried' => $candidates], 404);
            }

            $raw = strtolower((string) ($request->input('status') ?: $request->input('status_alias') ?: ''));
            $mappedStatus = OrderStatus::normalize($raw);

            $incomingMessage = trim((string) (
                $request->input('delivery_message')
                ?: $request->input('note')
                ?: $request->input('error_message')
                ?: $request->input('message')
                ?: ''
            ));
            $messageLower = strtolower($incomingMessage);
            $isInvalidUid = str_contains($messageLower, 'invalid uid')
                || str_contains($messageLower, 'wrong uid')
                || str_contains($messageLower, 'invalid region')
                || str_contains($messageLower, 'not bd server');

            if ($mappedStatus === OrderStatus::CANCEL) {
                DB::transaction(function () use ($order, $incomingMessage, $isInvalidUid) {
                    if ($order->relationLoaded('user')) {
                        $order->unsetRelation('user');
                    }

                    $lockedOrder = Order::query()->with('user')->lockForUpdate()->find($order->id);
                    if (!$lockedOrder) {
                        return;
                    }

                    $alreadyCancelled = OrderStatus::normalize($lockedOrder->status) === OrderStatus::CANCEL;
                    $previousStatus = OrderStatus::normalize($lockedOrder->status);
                    $canRefund = !in_array($previousStatus, [
                        OrderStatus::COMPLETED,
                        OrderStatus::CANCEL,
                    ], true);

                    if (!$alreadyCancelled && $canRefund && $lockedOrder->user) {
                        $refundAmount = (float) ($lockedOrder->amount ?? 0);
                        if ($refundAmount > 0) {
                            $lockedOrder->user->increment('balance', $refundAmount);
                        }
                    }

                    $lockedOrder->status = OrderStatus::CANCEL;

                    $cancelMessage = $isInvalidUid
                        ? 'UID ভুল হওয়ায় আপনার অর্ডারটি ক্যান্সেল করা হয়েছে। টাকা আপনার ব্যালেন্সে ফেরত দেওয়া হয়েছে।'
                        : 'আপনার অর্ডারটি ক্যান্সেল করা হয়েছে। টাকা আপনার ব্যালেন্সে ফেরত দেওয়া হয়েছে।';

                    if (Schema::hasColumn('orders', 'delivery_message')) {
                        $lockedOrder->delivery_message = $cancelMessage;
                    }
                    if (Schema::hasColumn('orders', 'admin_note')) {
                        $lockedOrder->admin_note = $cancelMessage;
                    }

                    $lockedOrder->save();
                });

                $order->refresh();
            } else {
                if (in_array($mappedStatus, [
                    OrderStatus::COMPLETED,
                    OrderStatus::PENDING,
                    OrderStatus::PROCESSING,
                    OrderStatus::AUTOPROCESSING,
                ], true)) {
                    $order->status = $mappedStatus;
                }

                if ($incomingMessage !== '') {
                    if (Schema::hasColumn('orders', 'delivery_message')) {
                        $order->delivery_message = $incomingMessage;
                    }
                    if (Schema::hasColumn('orders', 'admin_note')) {
                        $order->admin_note = $incomingMessage;
                    }
                }
            }

            // Replacement UID
            $newUid = trim((string)(
                $request->input('replacement_uid')
                ?: $request->input('delivered_uid')
                ?: $request->input('game_id')
                ?: $request->input('uid')
                ?: $request->input('player_id')
                ?: ''
            ));
            $incomingPlayerIdLabel = trim((string)(
                $request->input('player_id_label')
                ?: $request->input('uid_label')
                ?: ''
            ));
            if ($newUid !== '') {
                foreach (['game_id','uid','player_id','user_game_id','player_uid','account_id'] as $col) {
                    if (Schema::hasColumn('orders', $col)) $order->{$col} = $newUid;
                }
                $casts = $order->getCasts();
                $replaceDeep = function (&$node) use (&$replaceDeep, $newUid) {
                    if (!is_array($node)) return;
                    foreach ($node as $k => &$v) {
                        if (is_array($v)) $replaceDeep($v);
                        elseif (in_array(strtolower((string)$k), ['player_id','uid','game_id','user_id','user_game_id','account_id','player_uid'], true)) $v = $newUid;
                    }
                };
                foreach (['account_info','account_info_to','provider_data','custom_field_values','meta','extra'] as $jc) {
                    if (!Schema::hasColumn('orders', $jc)) continue;
                    $existing = $order->{$jc};
                    if (is_string($existing)) { $d = json_decode($existing, true); $existing = is_array($d) ? $d : []; }
                    elseif (!is_array($existing)) $existing = [];
                    $replaceDeep($existing);
                    $existing['player_id'] = $newUid; $existing['uid'] = $newUid; $existing['game_id'] = $newUid;
                    if ($incomingPlayerIdLabel !== '') {
                        $existing['player_id_label'] = $incomingPlayerIdLabel;
                    }
                    $isArr = isset($casts[$jc]) && in_array($casts[$jc], ['array','json','object','collection'], true);
                    $order->{$jc} = $isArr ? $existing : json_encode($existing, JSON_UNESCAPED_UNICODE);
                }
            }

            $order->save();
            return response()->json(['ok' => true, 'order_id' => $order->id, 'status' => $order->status, 'updated_uid' => $newUid]);
        } catch (Throwable $e) {
            Log::error('tastnow callback fatal: '.$e->getMessage());
            return response()->json(['ok' => false, 'error' => 'server error', 'message' => $e->getMessage()], 500);
        }
    }
}
