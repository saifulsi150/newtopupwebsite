<?php

namespace App\Http\Controllers;

use App\Constants\OrderStatus;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MainPanelCallbackController extends Controller
{
    public function handle(Request $request)
    {
        try {
            $expectedSecret = env('MYWEBSITE_SECRET_KEY', 'saiful150989');
            $receivedSecret = $request->header('X-Secret-Key')
                ?: $request->header('x-secret-key')
                ?: $request->input('api_key')
                ?: $request->input('apiKey')
                ?: $request->input('key');

            if (!$expectedSecret || !hash_equals((string) $expectedSecret, (string) $receivedSecret)) {
                return response()->json(['ok' => false, 'error' => 'unauthorized'], 401);
            }

            $orderId = $request->input('order_id')
                ?: $request->input('external_order_id')
                ?: $request->input('external_ref')
                ?: $request->input('website_order_id');

            if (!$orderId) {
                return response()->json(['ok' => false, 'error' => 'order_id required'], 400);
            }

            $order = Order::find($orderId);
            if (!$order) {
                return response()->json(['ok' => false, 'error' => 'order not found'], 404);
            }

            $status = strtolower((string) $request->input('status', ''));
            $deliveryMessage = (string) $request->input('delivery_message', '');
            $uid = (string) ($request->input('delivered_uid') ?: $request->input('uid') ?: $request->input('player_id') ?: $request->input('game_id'));

            // ─── COMPLETE ───────────────────────────────────────────────
            if (in_array($status, ['completed', 'complete', 'success', 'finish'], true)) {
                $order->status = $this->validStatusValue('complete');

                // account_info কলামে player_id JSON হিসাবে সেভ (আগেরটা replace হবে)
                if ($uid !== '' && array_key_exists('account_info', $order->getAttributes())) {
                    $order->account_info = json_encode(['player_id' => $uid]);
                }

                // voucher_code থাকলে সেখানেও রাখা
                if ($uid !== '' && array_key_exists('voucher_code', $order->getAttributes())) {
                    $order->voucher_code = $uid;
                }

                // delivery_message: main panel থেকে আসলে সেট, না আসলে null
                $order->delivery_message = $deliveryMessage ?: null;

                $order->save();

                return response()->json(['ok' => true, 'message' => 'Order completed', 'status' => $order->status]);
            }

            // ─── CANCEL ─────────────────────────────────────────────────
            if (in_array($status, ['cancelled', 'canceled', 'cancel', 'refunded', 'failed', 'fail', 'error'], true)) {
                $cancelStatus = $this->validStatusValue('cancel');
                $alreadyCancelled = ((string) $order->status === (string) $cancelStatus);

                if (!$alreadyCancelled && $order->user) {
                    $refund = (float) ($order->amount ?? 0);
                    if ($refund > 0) {
                        $order->user->increment('balance', $refund);
                        if (isset($order->user->total_order)) $order->user->decrement('total_order');
                        if (isset($order->user->total_spent)) $order->user->decrement('total_spent', $refund);
                    }
                }

                $order->status = $cancelStatus;
                $order->delivery_message = $deliveryMessage ?: 'অর্ডারটি বাতিল হয়েছে। টাকা ওয়ালেটে ফেরত দেওয়া হয়েছে।';
                $order->save();

                return response()->json(['ok' => true, 'message' => 'Order cancelled/refunded', 'status' => $order->status]);
            }

            if (in_array($status, ['pending', 'running', 'processing', 'looking', 'auto-processing'], true)) {
                $order->status = OrderStatus::normalize($status);
                $order->delivery_message = $deliveryMessage ?: $order->delivery_message;
                $order->save();

                return response()->json(['ok' => true, 'message' => 'Order status updated', 'status' => $order->status]);
            }

            return response()->json(['ok' => false, 'error' => 'unknown status'], 400);

        } catch (\Throwable $e) {
            Log::error('Main panel callback server error', [
                'message' => $e->getMessage(),
                'order_id' => $request->input('order_id'),
            ]);
            return response()->json(['ok' => false, 'error' => 'server error', 'message' => $e->getMessage()], 500);
        }
    }

    private function validStatusValue(string $target): string
    {
        $allowed = $this->allowedOrderStatuses();

        $completeCandidates = ['complete', 'completed', 'success', 'done'];
        $cancelCandidates   = ['cancel', 'cancelled', 'canceled', 'refunded', 'failed'];
        $candidates = $target === 'complete' ? $completeCandidates : $cancelCandidates;

        foreach ($candidates as $candidate) {
            if (in_array($candidate, $allowed, true)) {
                return $candidate;
            }
        }

        return $target === 'complete' ? 'complete' : 'cancel';
    }

    private function allowedOrderStatuses(): array
    {
        try {
            $row  = DB::selectOne("SHOW COLUMNS FROM orders LIKE 'status'");
            $type = $row->Type ?? $row->type ?? '';
            if (preg_match('/^enum\((.*)\)$/', $type, $matches)) {
                preg_match_all("/'((?:[^'\\\\]|\\\\.)*)'/", $matches[1], $values);
                return array_map(fn ($v) => stripcslashes($v), $values[1] ?? []);
            }
        } catch (\Throwable $e) {
            Log::warning('Could not inspect order status enum', ['message' => $e->getMessage()]);
        }

        return ['pending', 'running', 'looking', 'complete', 'cancel', 'processing', 'auto-processing'];
    }
}