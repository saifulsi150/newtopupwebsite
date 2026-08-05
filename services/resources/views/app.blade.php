<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title inertia>{{ config('app.name', 'LX TOPUP') }}</title>
    @vite(['resources/js/app.js'])
    @inertiaHead

    <link rel="preload" href="{{ asset('assets/template/css/styles.css') }}?v={{ @filemtime(public_path('assets/template/css/styles.css')) ?: time() }}" as="style">
    <link rel="preload" href="{{ asset('assets/template/css/custom-styles.css') }}?v={{ @filemtime(public_path('assets/template/css/custom-styles.css')) ?: time() }}" as="style">
    <link rel="stylesheet" href="{{ asset('assets/template/css/bootstrap/bootstrap.min.css') }}" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="{{ asset('assets/template/fonts/fontawesome/css/all.min.css') }}" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="{{ asset('assets/template/js/toastr/toastr.min.css') }}" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="{{ asset('assets/template/css/styles.css') }}?v={{ @filemtime(public_path('assets/template/css/styles.css')) ?: time() }}" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="{{ asset('assets/template/css/tailwindcss.css') }}" media="print" onload="this.media='all'">
    <link rel="stylesheet" href="{{ asset('assets/template/css/custom-styles.css') }}?v={{ @filemtime(public_path('assets/template/css/custom-styles.css')) ?: time() }}" media="print" onload="this.media='all'">
</head>
<body>
    @inertia
</body>
</html>
