@php
    $pwaIcon = $settings->pwa_icon ?: ($settings->favicon ?: $settings->logo);
    $pwaIconUrl = $pwaIcon ? get_image($pwaIcon) : asset('assets/template/img/logo.png');
@endphp

<!-- Web Application Manifest -->
<link rel="manifest" href="{{ route('manifest') }}">
<!-- Chrome for Android theme color -->
<meta name="theme-color" content="{{ $config['theme_color'] }}">

<!-- Add to homescreen for Chrome on Android -->
<meta name="mobile-web-app-capable" content="{{ $config['display'] == 'standalone' ? 'yes' : 'no' }}">
<meta name="application-name" content="{{ $config['short_name'] }}">
<link rel="icon" sizes="512x512" href="{{ $pwaIconUrl }}">

<!-- Add to homescreen for Safari on iOS -->
<meta name="apple-mobile-web-app-capable" content="{{ $config['display'] == 'standalone' ? 'yes' : 'no' }}">
<meta name="apple-mobile-web-app-status-bar-style" content="{{ $config['status_bar'] }}">
<meta name="apple-mobile-web-app-title" content="{{ $config['short_name'] }}">
<link rel="apple-touch-icon" href="{{ $pwaIconUrl }}">

<!-- Tile for Win8 -->
<meta name="msapplication-TileColor" content="{{ $config['background_color'] }}">
<meta name="msapplication-TileImage" content="{{ $pwaIconUrl }}">


