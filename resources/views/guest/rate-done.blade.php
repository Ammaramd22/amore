<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $done ? 'Thanks' : 'Rating' }} — {{ $companyName }}</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@600;700;800&family=Fraunces:wght@700&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0; min-height: 100vh; display: grid; place-items: center;
            font-family: 'DM Sans', system-ui, sans-serif; background: #0c0a09; color: #fafaf9; padding: 1.25rem;
        }
        .card {
            text-align: center; max-width: 400px; background: #1c1917; border-radius: 22px;
            padding: 2rem 1.5rem; border: 1px solid rgba(255,255,255,.08);
        }
        .emoji { font-size: 3.5rem; margin-bottom: .75rem; }
        h1 { font-family: Fraunces, Georgia, serif; font-size: 1.55rem; margin: 0 0 .5rem; }
        p { color: #a8a29e; margin: 0; line-height: 1.45; }
    </style>
</head>
<body>
    <div class="card">
        @if(!empty($emoji))
            <div class="emoji">{{ $emoji }}</div>
        @endif
        <h1>{{ $done ? 'Thank you!' : 'Unavailable' }}</h1>
        <p>{{ $message }}</p>
    </div>
</body>
</html>
