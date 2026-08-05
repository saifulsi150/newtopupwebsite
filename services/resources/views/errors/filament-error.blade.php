<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Maintenance</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 600px;
            padding: 50px;
            text-align: center;
        }
        .icon {
            width: 100px;
            height: 100px;
            background: #f0f0f0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 50px;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        p {
            color: #666;
            line-height: 1.8;
            margin-bottom: 15px;
            font-size: 16px;
        }
        .message {
            background: #fef3cd;
            border: 1px solid #ffc107;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
            font-size: 14px;
            color: #856404;
            text-align: left;
        }
        .code {
            background: #f5f5f5;
            padding: 10px 15px;
            border-radius: 3px;
            font-family: monospace;
            font-size: 12px;
            overflow-x: auto;
            margin-top: 10px;
        }
        .btn {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 12px 30px;
            border-radius: 5px;
            text-decoration: none;
            margin-top: 20px;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #764ba2;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🛠️</div>
        <h1>Admin Panel Maintenance</h1>
        <p>অ্যাডমিন প্যানেল এখন সেটআপ করা হচ্ছে।</p>
        <p>ডাটাবেজ সংযোগ কনফিগার করা হচ্ছে, অনুগ্রহ করে কয়েক মুহূর্ত অপেক্ষা করুন।</p>
        
        <div class="message">
            <strong>🔧 সিস্টেম বার্তা:</strong>
            <p style="margin-top: 10px;">আপনার অ্যাডমিন প্যানেল শীঘ্রই সম্পূর্ণ হবে। যদি এই পেজ বারবার দেখা যায়, তাহলে আপনার ডাটাবেজ সেটিংস যাচাই করুন।</p>
            @if(isset($error))
            <div class="code">{{ $error }}</div>
            @endif
        </div>
        
        <a href="/" class="btn">← ফিরে যান</a>
    </div>
</body>
</html>
