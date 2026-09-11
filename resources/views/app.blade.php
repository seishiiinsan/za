<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- Surface publique : aucune métadonnée système. --}}
    <meta name="referrer" content="same-origin">
    <title inertia>{{ config('app.name', 'Za') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>
<body class="min-h-screen bg-neutral-950 text-neutral-100 antialiased">
    @inertia
</body>
</html>
