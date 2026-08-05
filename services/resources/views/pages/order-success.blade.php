<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ __('Order Successful') }} - {{ $settings->site_title ?? 'Success' }}</title>
    
    <style>
        /* বেসিক রিসেট */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        body {
            background-color: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 15px; /* স্ক্রিনের চারপাশে জায়গা রাখবে */
            color: #1f2937;
        }
        
        /* মেইন কার্ড যা যেকোনো মোবাইলে ফিট হবে */
        .success-card {
            background: #ffffff;
            width: 100%;
            max-width: 400px;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            animation: popIn 0.3s ease-out;
        }

        /* কার্ডের উপরের সবুজ অংশ */
        .card-header {
            background: #059669;
            padding: 25px 20px 20px;
            text-align: center;
            color: #ffffff;
            position: relative;
        }

        .status-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255, 255, 255, 0.2);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .icon-box {
            width: 46px;
            height: 46px;
            background: #ffffff;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #059669;
            margin-bottom: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .icon-box svg {
            width: 24px;
            height: 24px;
        }

        .card-title {
            font-size: 20px;
            font-weight: 800;
            margin-bottom: 4px;
        }

        .card-subtitle {
            font-size: 13px;
            opacity: 0.9;
        }

        /* কার্ডের ভেতরের ইনফরমেশন অংশ */
        .card-body {
            padding: 20px;
        }

        .order-id {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 12px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 15px;
        }

        .order-id span {
            display: block;
            font-size: 11px;
            color: #64748b;
            font-weight: 700;
            text-transform: uppercase;
        }

        .order-id strong {
            display: block;
            font-size: 18px;
            color: #0f172a;
            margin-top: 2px;
        }

        /* লিস্ট আইটেমগুলো */
        .info-list {
            margin-bottom: 20px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px dashed #e2e8f0;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-size: 12px;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
        }

        .info-value {
            font-size: 13px;
            color: #1e293b;
            font-weight: 700;
            text-align: right;
            max-width: 60%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .info-value.amount {
            color: #059669;
            font-size: 15px;
        }

        /* বাটন এরিয়া */
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .btn {
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            text-align: center;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-primary {
            background: #059669;
            color: #ffffff;
        }

        .btn-primary:active {
            background: #047857;
            transform: scale(0.98);
        }

        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        .btn-secondary:active {
            background: #e2e8f0;
            transform: scale(0.98);
        }

        /* অ্যানিমেশন */
        @keyframes popIn {
            from { opacity: 0; transform: scale(0.95) translateY(10px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
    </style>
</head>
<body>

    <div class="success-card">
        <!-- Top Green Section -->
        <div class="card-header">
            <div class="status-badge">{{ \App\Constants\OrderStatus::text($order->status) }}</div>
            
            <div class="icon-box">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            
            <h1 class="card-title">{{ __('Order Successful') }}</h1>
            <p class="card-subtitle">{{ __('Your order has been placed successfully.') }}</p>
        </div>

        <!-- Details Section -->
        <div class="card-body">
            <div class="order-id">
                <span>{{ __('Order ID') }}</span>
                <strong>{{ $order->order_id_to ?? ('#' . $order->id) }}</strong>
            </div>

            <div class="info-list">
                <div class="info-row">
                    <span class="info-label">{{ __('Package') }}</span>
                    <span class="info-value">{{ optional($order->variation)->title ?? optional($order->variation)->name ?? '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">{{ __('Product') }}</span>
                    <span class="info-value">{{ optional($order->product)->name ?? '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">{{ __('Quantity') }}</span>
                    <span class="info-value" style="font-family: monospace; font-size: 14px;">{{ $order->quantity }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">{{ __('Amount') }}</span>
                    <span class="info-value amount">{{ number_format($order->amount, 2) }} {{ $settings->cur_text ?? '' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">{{ __('Payment') }}</span>
                    <span class="info-value">{{ optional($order->transaction)->method ?? '-' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">{{ __('Transaction ID') }}</span>
                    <span class="info-value">{{ optional($order->transaction)->transaction_id ?? '-' }}</span>
                </div>
                @if (!empty($order->voucher_code))
                    <div class="info-row">
                        <span class="info-label">{{ __('Voucher Code') }}</span>
                        <span class="info-value">{{ $order->voucher_code }}</span>
                    </div>
                @endif
                <div class="info-row">
                    <span class="info-label">{{ __('Date') }}</span>
                    <span class="info-value" style="font-size: 11px;">{{ $order->created_at->format('d M Y, h:i A') }}</span>
                </div>
            </div>

            <!-- Buttons -->
            <div class="action-buttons">
                <a href="{{ route('orders') }}" class="btn btn-primary">{{ __('My Orders') }}</a>
                <a href="{{ url('/') }}" class="btn btn-secondary">{{ __('Back To Home') }}</a>
            </div>
        </div>
    </div>

</body>
</html>