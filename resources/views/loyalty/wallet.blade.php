<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Add stamp card to Wallet</title>
    <style>
        body{margin:0;font-family:"Avenir Next","Segoe UI",system-ui,-apple-system,sans-serif;background:#1a0a0e;color:#fafaf9;padding:28px 18px}
        .box{max-width:480px;margin:0 auto;background:#4A0E1A;border:1px solid rgba(232,194,138,.25);border-radius:20px;padding:22px;box-shadow:0 20px 50px rgba(0,0,0,.4)}
        h1{font-size:1.25rem;margin:0 0 8px}
        p{color:rgba(255,255,255,.7);line-height:1.5;font-size:.92rem}
        .step{background:rgba(0,0,0,.25);border-radius:12px;padding:12px 14px;margin:10px 0;border:1px solid rgba(255,255,255,.06)}
        a.btn{display:block;text-align:center;margin-top:16px;padding:14px;border-radius:12px;background:linear-gradient(135deg,#e8c28a,#c9a06a);color:#2d0810;text-decoration:none;font-weight:700}
        .note{font-size:.8rem;color:rgba(255,255,255,.45);margin-top:14px}
    </style>
</head>
<body>
<div class="box">
    <h1>Add {{ $business }} stamp card</h1>
    <p>Hi {{ $customer->name }} — keep your stamp card on your phone. Staff scans the QR each time you buy.</p>

    <div class="step"><strong>Apple iPhone</strong><br>Open the stamp card → Share → Add to Home Screen. Full Apple Wallet .pkpass needs restaurant Apple certificates (owner can enable later).</div>
    <div class="step"><strong>Android / Google</strong><br>Open the stamp card → Chrome menu → Add to Home screen / Install app. Google Wallet pass API can be connected by owner later.</div>
    <div class="step"><strong>Works today</strong><br>Save the card link and show the QR at the counter. {{ $payload['stamps_required'] }} stamps = {{ $payload['reward_label'] }}@if(!empty($payload['expires_at'])) · expires {{ \Illuminate\Support\Carbon::parse($payload['expires_at'])->format('d M Y') }}@endif.</div>

    <a class="btn" href="{{ $cardUrl }}">Open my stamp card</a>
    <p class="note">Signed Apple Wallet / Google Wallet passes require developer certificates. The digital QR stamp card works on every phone now.</p>
</div>
</body>
</html>
