<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Alami Admin') }}</title>
    <link rel="icon" href="{{ asset('img/logo.png') }}" type="image/png">
    <style>
        :root { color-scheme: light; font-family: Arial, sans-serif; }
        body { align-items: center; background: #f4f4f5; color: #333; display: flex; justify-content: center; min-height: 100vh; margin: 0; }
        main { background: #fff; border-top: 4px solid #605ca8; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,.12); max-width: 420px; padding: 32px; text-align: center; }
        img { height: 80px; margin-bottom: 16px; max-width: 160px; object-fit: contain; }
        h1 { font-size: 24px; margin: 0 0 12px; }
        p { color: #666; line-height: 1.5; margin: 0; }
    </style>
</head>
<body>
    <main>
        <img src="{{ asset('img/logo.png') }}" alt="{{ config('app.name', 'Alami Admin') }}">
        <h1>Anda sedang offline</h1>
        <p>Periksa koneksi internet Anda, lalu muat ulang halaman ini untuk melanjutkan.</p>
    </main>
</body>
</html>
