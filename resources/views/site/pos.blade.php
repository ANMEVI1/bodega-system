<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Punto de Venta / Caja · {{ $businessName }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@500;600;700&family=Newsreader:ital,opsz,wght@0,6..72,400..700;1,6..72,400..600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @viteReactRefresh
    @vite(['resources/css/pos.css', 'resources/js/pos/main.tsx'])
</head>
<body class="bg-[#F4EFE4] text-[#192315] antialiased">
    <div id="pos-root"
         data-business-name="{{ $businessName }}"
         data-currency="{{ $currency }}"
         data-home-url="{{ $homeUrl }}"></div>
</body>
</html>
