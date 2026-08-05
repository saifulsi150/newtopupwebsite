@if (session('message'))     <script type="text/javascript">     document.addEventListener('DOMContentLoaded', function () {       if (!window.toastr) {         return;       }        const messageType = "{{ session('message_type') }}";       const messageText = "{{ session('message') }}";        if (typeof toastr[messageType] === 'function') {         toastr[messageType](messageText);       }     });     </script> @endif <!DOCTYPE html> <html lang="en"> <head>   <meta charset="utf-8">     <meta http-equiv="X-UA-Compatible" content="IE=edge">     <meta name="viewport" content="width=device-width, initial-scale=1">     <meta name="theme-color" content="{{ $settings->theme_color ?? '#0a6b2a' }}">     <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">     <meta name="description" content="{{ $settings->seo_description }}" />     <meta name="keywords" content="{{ $settings->seo_keywords }}" />     <meta property="og:url" content="{{ url()->current() }}">     {{-- Twitter --}}     <meta property="twitter:card" content="summary_large_image">     <meta property="twitter:url" content="{{ url()->current() }}">     <meta property="twitter:title" content="@yield('title', 'Site Title')">     <meta property="twitter:description" content="{{ $settings->seo_description }}">     @if (!empty($settings->twitter_og_image))         <meta property="twitter:image" content="{{ get_image($settings->twitter_og_image) }}">     @endif      {{-- Bootstrap --}}     <link rel="stylesheet" href="{{ asset('assets/template/css/bootstrap/bootstrap.min.css') }}">      {{-- Font Awesome --}}     <link rel="stylesheet" href="{{ asset('assets/template/fonts/fontawesome/css/all.min.css') }}">      {{-- Toastr --}}     <link rel="stylesheet" href="{{ asset('assets/template/js/toastr/toastr.min.css') }}">      {{-- Custom --}}     @php       $stylesVersion = @filemtime(public_path('assets/template/css/styles.css')) ?: '1';       $customStylesVersion = @filemtime(public_path('assets/template/css/custom-styles.css')) ?: '1';     @endphp     <link rel="stylesheet" href="{{ asset('assets/template/css/styles.css') }}?v={{ $stylesVersion }}">      <style>         :root {             --theme-color: {{ $settings->theme_color }};             --logo-color: {{ $settings->logo_color }};             --background-color: {{ $settings->background_color }};             --primary-font-color: {{ $settings->font_color }};             --navigation-background-color: {{ $settings->navigation_background_color }};             --navigation-font-color: {{ $settings->navigation_font_color }};             --footer-color: {{ $settings->footer_color }};             --footer-font-color: {{ $settings->footer_font_color }};             --content-box-color: {{ $settings->content_box_color }};              @if ($settings->background_image)                 --background-image: linear-gradient(350deg, rgb(244, 249, 255), rgba(237, 244, 255, 0.79)),                     url(data:image/png;base64,UklGRtQEAABXRUJQVlA4TMgEAAAvj8FjAAVFA4Bqa9f7P14MQ4yIiBgSsRgW316rZvat/3ZOEf135LaNJGVOc3Nplk5c8QfO8avRaXkRWzSJMwnO2STKiUVTMWJoE02L4ldsGHwygXeHpUKw1OFMGJK3gS8r5EeuWk4KaKnosum7bvrOZhcthXAYqjpaIL8GxVvYZiz66o7BwCiFkAxsgV03/LowWxj5Am15/DrU00fDYaTpjyo+n9GrATIcB7BecDyzXnCHJoaPLPqzQl8cvwrlcaKgO47Eh1gG5XEaxodgr/wzyA/BHbLyL8s/2EVUfjEL8ENwVGIID8EZ6sL4NSaoN1KxzkgcLQHJlRSWNjtQZ7pRzS7WPjtmS0gXKZ0wzQXX6PKNFPFccMErVSFIbYqhxhvVCsRtUfzIB5a7vkSTr18DSoPjcfRuIhipDAQXwsn31lFsA/WRJoIzUrlzkUJIE8E+8oBzImhjwUBpIJiDp0Xxa9MO3zREOJvgWU0Fm4aIRibB1EyC/wnmx+SmBbENnsY4Lz944nJQCA4DwRnnhfm4nzrAtFzgwdPYzwvjQHBUCCZeDb+OxDKfnTIkuTWdiimp3lrZ7pHnM8JRbq1sgVgVguO1lRXCohDM6Put6VQI7rdWVhLkOQZhwr4UflVrpAldZivtUFaBDUOZxItgrfvQIP4owyiGUY+hake9y4xISsHF8UL4HcAGG9XqPQBbvAeD/SRQTB4KSAODh5Isxi7DsQ5+lG3e3GHy5sI7eozB5DEeRo+R1sFvHR78Ovit445qHfyyDYNYit/1Dre87g73l5Ufr5/fu/6/8Ae+WQc/EMtJjc9f957PX2d8/hbTd30d/F7Yn8b37E/Ty/rTTOvg16Fa/nrS6+a3Ypjf0DS/8Uvnt3XwO9mxUm8w+RvN6G/Aj9aISg4d42MqMPobzeRvBJ3gC7qF8DsbUpW53AxXqQmS0f9TCC5IcntYdJWNZ/D/xOj/JUjXDxWyQnAlbOdS+J1SvMkfj6Dwx7MYjHU38seTxh9PI3/cWfzxrPDHweaP+yLnMvit4z7vX2B+PvCoFyI07K/VSE6/+Caa/bXUBuqTZn9N9GtojmI17K8hjXoiDn5R/I5WPNbrlbKLtU03MdOtW+2IZbZtKW243xm4yUTwbL+zTwWH4X5nkykGxH7rSNN0a7TV6NJFZkX/g2Ed/JSX2OLpsOw/O8v+80m+6wTf959FN815Oi37z86y/3yQl8HV+Lr4nf1HD+m9h4jSPy4f4LqgAQNdcg6L43dWKCgGHzX7l+VnYnxZfsZnMgjGAvVcHr+TTBcN/TPv1LtBEzs6l8HvZXc7mCxVX3lHVU13VGi8U18Hv9+cO/X/cOTet/+593nuffufez++Mb/tf+5dmXvf/ufe65flt/3PvT819779z72/f+59+597H+fet/+591Huffufe3efwW/7n3v/dz33vv3PvY9y79v/3DuOa/ufe3cfwG/7n3v349r+5963/7n3NeTet/+593vuffufe/+Y3Pv2P/cOGgzb/9z79j/3XpaRey/btNz79j/3Dg8M2//cO8wEb/9z72+fe9/+595Bh2H7n3vf/ufev3ruffufex/k3rf/ufcPzL1v/3Pvv2659+1/7n1Fufftf+59+597PwE=);             @endif         }     </style>      <style>         @if (!$settings->footer_menu)             .sticky-footer-container {                 display: none;             }              body {                 margin-bottom: 0px;             }         @endif          .page-content {             transition: opacity 160ms ease;         }          .page-content.is-swapping {             opacity: 0.7;         }     </style>      @stack('style')       {{-- extra --}}     <link rel="stylesheet" href="{{ asset('assets/template/css/tailwindcss.css') }}">     <link rel="stylesheet" href="{{ asset('assets/template/css/custom-styles.css') }}?v={{ $customStylesVersion }}">       <script>       !function (w, d, t) {         w.TiktokAnalyticsObject = t;         var ttq = w[t] = w[t] || [];         ttq.methods = ['page', 'track', 'identify', 'instances', 'debug', 'on', 'off', 'once', 'ready', 'alias', 'group', 'enableCookie', 'disableCookie'];         ttq.setAndDefer = function (t, e) {           t[e] = function () {             t.push([e].concat(Array.prototype.slice.call(arguments, 0)));           };         };         for (var i = 0; i < ttq.methods.length; i++) {           ttq.setAndDefer(ttq, ttq.methods[i]);         }         ttq.instance = function (t) {           var e = ttq._i[t] || [];           for (var n = 0; n < ttq.methods.length; n++) {             ttq.setAndDefer(e, ttq.methods[n]);           }           return e;         };         ttq.load = function (e, n) {           var r = 'https://analytics.tiktok.com/i18n/pixel/events.js';           ttq._i = ttq._i || {};           ttq._i[e] = [];           ttq._i[e]._u = r;           ttq._t = ttq._t || {};           ttq._t[e] = +new Date();           ttq._o = ttq._o || {};           ttq._o[e] = n || {};           var o = document.createElement('script');           o.type = 'text/javascript';           o.async = true;           o.src = r;           var a = document.getElementsByTagName('script')[0];           a.parentNode.insertBefore(o, a);         };         ttq.load('D7SQVFBC77UDOFSGCVDG');         ttq.page();       }(window, document, 'ttq');     </script>      {!! $settings->header_tags !!} </head>  <body> @php   $rawSupportContact = trim((string) ($settings->whatsapp_number ?? ''));   $supportUrl = '';    if ($rawSupportContact !== '') {     if (preg_match('/^https?:\/\//i', $rawSupportContact)) {       $supportUrl = $rawSupportContact;     } else {       $digits = preg_replace('/\D+/', '', $rawSupportContact);       if ($digits !== '') {         $supportUrl = str_starts_with($digits, '88')           ? 'https://wa.me/' . $digits           : 'https://wa.me/88' . $digits;       }     }   }    if ($supportUrl === '') {     $supportUrl = 'https://t.me/admimapp';   } @endphp @if (false) <div id="page-loading-bar" class="page-loading-bar" aria-hidden="true">   <div class="page-loading-bar__fill"></div> </div> @endif <div class="body-bg"> @auth <div class="header">     <div class="container m-auto p-2 py-3 md:py-5 md:px-0">         <nav class="flex items-center justify-between">             <a href="/" class="">                 <img alt="Logo" data-nuxt-img="" srcset="{{ get_image($settings->logo) }} 1x, {{ get_image($settings->logo) }} 2x" class="w-28 md:w-48 logo" src="{{ get_image($settings->logo) }}">             </a>             <div class="relative">                 <div class="flex items-center">                     <nav class="text-left hidden md:block">                         <div class="w-full flex-grow flex items-center lg:w-auto">                             <div class="text-sm flex-grow animated jackinthebox mx-auto">                                 <a href="/#topup" class="block inline-block text-md font-bold mx-2 p-1 rounded-lg fb-normal link"> Topup                                  </a>                                 <a href="{{ $supportUrl }}" target="_blank" rel="noopener" class="block inline-block text-md font-bold mx-2 p-1 rounded-lg fb-normal link"> Contact Us                                  </a>                             </div>                         </div>                     </nav>                     <a href="{{ route('account') }}" class="router-link-active router-link-exact-active flex items-center text-md px-4 py-2 shadow-md hover:shadow-2xl border rounded-full text-black bg-pink-500 text-white font-primary" aria-current="page">                         <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-wallet w-4 h-4"><path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"></path><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"></path>                         </svg>                         <span class="ml-1" id="header_wallet_balance">{{ amount(Auth::user()->balance) }}?</span>                     </a>                     <div class="flex items-center cursor-pointer px-2 duration-75 w-16 profile">                       <img src="{{ Auth::user()->picture ?: ('https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->name ?? 'User') . '&size=96&background=f29f2c&color=ffffff') }}" class="rounded-full" alt="Profile">                     </div>                 </div>                 <div id="userMenu" class="hidden bg-white rounded shadow-md absolute mt-12 top-0 right-0 min-w-full overflow-auto z-30">                     <nav class="flex fixed items-center justify-between h-16 bg-white text-gray-700 border-b border-gray-200 z-10 gosizi-navlist" style="position: fixed; bottom: 0px;">                       <div class="z-10 fixed inset-0 transition-opacity">                         <div tabindex="0" class="absolute inset-0 bg-black opacity-50 nav-overlay"></div>                       </div>                       <aside class="transform top-0 right-0 w-64 bg-white fixed h-full overflow-auto ease-in-out transition-all duration-300 z-30 translate-x-0">                         <button id="userButton" class="flex items-center focus:outline-none p-3">                           <img src="{{ Auth::user()->picture ?: ('https://ui-avatars.com/api/?name=' . urlencode(Auth::user()->name ?? 'User') . '&size=96&background=f29f2c&color=ffffff') }}" backgroundcolor="#D81C4B" color="#fff" style="height: 50px;" alt="Profile">                            <div>                             <div class="text-left w-full">                               <span class="px-3 font-normal font-primary">Hi,                                  {{ Auth::user()->name }}</span>                             </div>                             <div class="text-left">                               <span class="px-3">{{ Auth::user()->email }}</span>                             </div>                           </div>                         </button>                         <div class="w-full mx-auto text-center">                         <a href="{{ route('logout') }}" class="inline-block">                           <button type="button" class="align-middle bg-pink-500 rounded-full mx-auto text-center hover:bg-pink-400 text-center px-1 py-2 text-white text-sm font-semibold rounded-lg inline-block shadow-lg px-6 mb-2 d-block btn-primary gosizi-btn">                             <!---->                             <span class="flex items-center justify-center p-0">                               <span class="mr-2">                                 <svg aria-hidden="true" focusable="false" data-prefix="far" data-icon="power-off" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" style="width: 0.83rem;">                                   <path fill="currentColor" d="M388.5 46.3C457.9 90.3 504 167.8 504 256c0 136.8-110.8 247.7-247.5 248C120 504.3 8.2 393 8 256.4 7.9 168 54 90.3 123.5 46.3c5.8-3.7 13.5-1.8 16.9 4.2l11.8 20.9c3.1 5.5 1.4 12.5-3.9 15.9C92.8 122.9 56 185.1 56 256c0 110.5 89.5 200 200 200s200-89.5 200-200c0-70.9-36.8-133.1-92.3-168.6-5.3-3.4-7-10.4-3.9-15.9l11.8-20.9c3.3-6.1 11.1-7.9 16.9-4.3zM280 276V12c0-6.6-5.4-12-12-12h-24c-6.6 0-12 5.4-12 12v264c0 6.6 5.4 12 12 12h24c6.6 0 12-5.4 12-12z"></path>                                 </svg>                               </span>                               <span class="no-underline text-xs">Logout</span>                             </span>                             <!---->                           </button>                       </a>                         </div>                         <hr>                         <a href="{{asset('account')}}" class="text-gray-900 no-underline">                           <span class="flex items-center p-4 font-primary">                             <span class="mr-2">                               <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" class="w-6 h-6">                                 <path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>                               </svg>                             </span> My Account </span>                         </a>                         <a href="{{asset('orders')}}" class="text-gray-900 no-underline">                           <span class="flex items-center p-4 font-primary">                             <span class="mr-2">                               <svg fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" viewBox="0 0 24 24" class="w-6 h-6">                                 <path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>                               </svg>                             </span> My Orders </span>                         </a>                         <a href="{{asset('codes')}}" class="text-gray-900 no-underline">                           <span class="flex items-center p-4 font-primary">                             <span class="mr-2">                               <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" class="css-i6dzq1">                                 <rect x="3" y="3" width="7" height="7"></rect>                                 <rect x="14" y="3" width="7" height="7"></rect>                                 <rect x="14" y="14" width="7" height="7"></rect>                                 <rect x="3" y="14" width="7" height="7"></rect>                               </svg>                             </span>                             <span> My Codes </span>                           </span>                         </a>                         {{-- âœ… My Transaction --}} <a href="{{ asset('transactions') }}" class="text-gray-900 no-underline">   <span class="flex items-center p-4 font-primary">     <span class="mr-2">       {{-- List icon --}}       <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24"         stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">         <path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01" />       </svg>     </span>     My Transaction   </span> </a>                         <a href="{{asset('add-funds')}}" class="text-gray-900 no-underline">                           <span class="flex items-center p-4 font-primary">                             <span class="mr-2">                               <svg class="w-6 h-6" viewBox="0 0 24 24">                                 <path fill="currentColor" d="M3 0V3H0V5H3V8H5V5H8V3H5V0H3M10 3V5H19V7H13C11.9 7 11 7.9 11 9V15C11 16.1 11.9 17 13 17H19V19H5V10H3V19C3 20.1 3.89 21 5 21H19C20.1 21 21 20.1 21 19V16.72C21.59 16.37 22 15.74 22 15V9C22 8.26 21.59 7.63 21 7.28V5C21 3.9 20.1 3 19 3H10M13 9H20V15H13V9M16 10.5A1.5 1.5 0 0 0 14.5 12A1.5 1.5 0 0 0 16 13.5A1.5 1.5 0 0 0 17.5 12A1.5 1.5 0 0 0 16 10.5Z"></path>                               </svg>                             </span> Add Fund </span>                         </a>                         <a href="{{ $supportUrl }}" target="_blank" rel="noopener" class="text-gray-900 no-underline">   <span class="flex items-center p-4 font-primary">     <span class="mr-2">       {{-- Info icon (from Contact Us) --}}       <svg fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"         viewBox="0 0 24 24" class="w-6 h-6">         <path d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>       </svg>     </span>     Contact Us   </span> </a>                         <hr>                         <div class="w-full mx-auto text-center mt-3">                           <a href="{{ $supportUrl }}" target="_blank" rel="noopener" class="align-middle bg-pink-500 rounded-full mx-auto text-center hover:bg-pink-400 text-center px-1 py-2 text-white text-sm font-semibold rounded-lg inline-block shadow-lg w-32 px-6 mb-2 d-block btn-primary gosizi-btn">                             <span class="flex items-center justify-center">                               <span class="mr-2">                                 <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 24 24" height="20" width="20" xmlns="http://www.w3.org/2000/svg">                                   <path d="M12 2C6.486 2 2 6.486 2 12v4.143C2 17.167 2.897 18 4 18h1a1 1 0 0 0 1-1v-5.143a1 1 0 0 0-1-1h-.908C4.648 6.987 7.978 4 12 4s7.352 2.987 7.908 6.857H19a1 1 0 0 0-1 1V18c0 1.103-.897 2-2 2h-2v-1h-4v3h6c2.206 0 4-1.794 4-4 1.103 0 2-.833 2-1.857V12c0-5.514-4.486-10-10-10z"></path>                                 </svg>                               </span>                               <span class="no-underline">Support</span>                             </span>                           </a>                         </div>                       </aside>                     </nav>                   </div>             </div>         </nav>     </div> </div> @else <div class="header">   <div class="container m-auto p-2 py-3 md:py-5 md:px-0">     <nav class="flex items-center justify-between">       <a href="/" class="">         <img src="{{ get_image($settings->logo) }}"  alt="{{ $settings->site_name }}" data-nuxt-img="" srcset="{{ get_image($settings->logo) }} 1x, {{ get_image($settings->logo) }} 2x" class="w-28 md:w-48 logo" fetchpriority="high" decoding="async">       </a>       <div class="relative">         <div class="flex items-center">           <nav class="text-left hidden md:block">             <div class="w-full flex-grow flex items-center lg:w-auto">               <div class="text-sm flex-grow animated jackinthebox mx-auto">                 <a href="#topup" class="block inline-block text-md font-bold mx-2 p-1 rounded-lg fb-normal link"> Topup </a>                 <a href="{{ $supportUrl }}" target="_blank" rel="noopener" class="block inline-block text-md font-bold mx-2 p-1 rounded-lg fb-normal link"> Contact Us </a>               </div>             </div>           </nav>           <div class="flex items-center">             <a href="/login" class="btn-pro  btn-register rounded ml-2 border-2 border-pink-500 bg-pink-500 text-white"> login </a>           </div>         </div>        <!---->       </div>     </nav>   </div> </div> @endauth  <main id="page-content" class="page-content" data-page-content>   @yield('content') </main>   <footer data-v-4c1ace0e="" class="mb-16 md:mb-0 text-gray-200 border-t-2 footer-bg">             <section data-v-4c1ace0e="" class="container mx-auto pb-8">               <div data-v-4c1ace0e="">                 <div data-v-4c1ace0e="" class="m-auto flex flex-wrap">                   <div data-v-4c1ace0e="" class="w-full md:w-4/6 m-auto flex flex-wrap my-0">                     <div data-v-4c1ace0e="" class="w-full md:w-1/3 px-5 md:px-0">                       <div data-v-4c1ace0e="" class="text-lg fb mt-10 uppercase text-white font-normal tracking-wider footer-title">STAY CONNECTED</div>                       <div data-v-4c1ace0e="" class="m-auto flex flex-wrap ff-bf mt-2 footer_nav">                         <div data-v-4c1ace0e="" class="w-full mt-1 md:mt-2">                           <a data-v-4c1ace0e="" class="flex ff-bf ffont-medium" href="#" style="line-height: 17px;">                             <span data-v-4c1ace0e="" class="text-xs">                              <p class="footer_description">আমাদের অফিসিয়াল Telegram এ যুক্ত হয়ে সর্বশেষ অফার সবার আগে পেয়ে যান</p>                             </span>                           </a>                           <div data-v-4c1ace0e="" class="mt-1 md:mt-2">                             <div data-v-4c1ace0e="" class="flex flex-wrap">                                                                  <div data-v-4c1ace0e="" class="social_icon mx-2 my-3 ml-0" data-aos="zoom-in" data-aos-duration="500">                                 <a data-v-4c1ace0e="" href="{{ $settings->facebook_link }}" target="_blank" aria-label="Social Icon" rel="noopener">                                   <svg data-v-4c1ace0e="" viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" class="css-i6dzq1">                                     <path data-v-4c1ace0e="" d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path>                                   </svg>                                 </a>                               </div>                                                                                <div data-v-4c1ace0e="" class="social_icon mx-2 my-3" data-aos="zoom-in" data-aos-duration="500">                                 <a data-v-4c1ace0e="" href="{{ $settings->messenger_link }}" target="_blank" aria-label="Social Icon" rel="noopener">                                   <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" class="css-i6dzq1">                                   <path d="M12 2C6.48 2 2 6.19 2 11.5c0 3.67 2.14 6.85 5.37 8.44V22l4.02-2.21c.86.21 1.75.32 2.61.32 5.52 0 10-4.19 10-9.5S17.52 2 12 2zm2.92 9.59l-3.14 3.33-2.25-2.4-5.51 5.87L9.64 8.91l3.14-3.33 2.25 2.4 5.51-5.87L14.92 11.59z"></path>                                 </svg>                                  </a>                               </div>                                                                       <div data-v-4c1ace0e="" class="social_icon mx-2 my-3" data-aos="zoom-in" data-aos-duration="500">                                 <a data-v-4c1ace0e="" href="{{ $settings->youtube_link }}" target="_blank" aria-label="Social Icon" rel="noopener">                                   <svg data-v-4c1ace0e="" viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" class="css-i6dzq1">                                     <path data-v-4c1ace0e="" d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"></path>                                     <polygon data-v-4c1ace0e="" points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"></polygon>                                   </svg>                                 </a>                               </div>                               <div data-v-4c1ace0e="" class="social_icon mx-2 my-3" data-aos="zoom-in" data-aos-duration="500">                                 <a data-v-4c1ace0e="" href="mailto:{{ $settings->email_address }}" target="_blank" aria-label="Social Icon" rel="noopener">                                   <svg data-v-4c1ace0e="" viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" class="css-i6dzq1">                                     <path data-v-4c1ace0e="" d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>                                     <polyline data-v-4c1ace0e="" points="22,6 12,13 2,6"></polyline>                                   </svg>                                 </a>                               </div>                                                                </a>                             </div>                           </div>                         </div>                       </div>                     </div>                   </div>                   <div data-v-4c1ace0e="" class="w-full md:w-2/6 footer_nav pt-5 px-5 md:px-0">                     <div data-v-4c1ace0e="" class="md:ml-20">                       <div data-v-4c1ace0e="" class="text-lg fb mt-1 uppercase text-white font-normal tracking-wider pb-3 footer-title">SUPPORT CENTER</div>                        <div data-v-f9030ba7="" class="ff-bf ffont-medium">                         <a data-v-f9030ba7="" href="{{ $settings->whatsapp_number }}" target="_blank" class="rounded-md p-3 mt-2 md:mt-4 flex footer-contact-icon1 border">                           <div data-v-f9030ba7="" class="footer-contact-icon">                             <svg data-v-f9030ba7="" height="34" color="#dfdfdf" width="34" aria-hidden="true" focusable="false" data-prefix="fab" data-icon="whatsapp" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" class="svg-inline--fa fa-whatsapp fa-w-14 fa-2x mr-2 pl-2"><path data-v-f9030ba7="" fill="currentColor" d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7.9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z" class=""></path>                             </svg>                           </div>                           <div data-v-f9030ba7="" class="ml-2 pl-2" style="border-left: 2px solid rgb(177, 177, 177);">                             <p data-v-f9030ba7="" class="text-primary text-opacity-70 text-xs font-normal" style="color: rgb(255, 255, 255);">                               Help line [{{ $settings->support_time }}]                              </p>                             <span data-v-f9030ba7="" class="number">                               Whatsapp HelpLine                              </span>                           </div>                         </a>                         <a data-v-f9030ba7="" href="{{ $settings->telegram_link }}" target="_blank" class="rounded-md p-3 mt-2 md:mt-4 flex footer-contact-icon1 border">                           <div class="footer-contact-icon" data-v-f9030ba7="">                             <svg stroke="currentColor" fill="currentColor" height="34" color="#dfdfdf" width="34" stroke-width="0" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg" data-v-f9030ba7=""><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8.287 5.906c-.778.324-2.334.994-4.666 2.01-.378.15-.577.298-.595.442-.03.243.275.339.69.47l.175.055c.408.133.958.288 1.243.294.26.006.549-.1.868-.32 2.179-1.471 3.304-2.214 3.374-2.23.05-.012.12-.026.166.016.047.041.042.12.037.141-.03.129-1.227 1.241-1.846 1.817-.193.18-.33.307-.358.336a8.154 8.154 0 0 1-.188.186c-.38.366-.664.64.015 1.088.327.216.589.393.85.571.284.194.568.387.936.629.093.06.183.125.27.187.331.236.63.448.997.414.214-.02.435-.22.547-.82.265-1.417.786-4.486.906-5.751a1.426 1.426 0 0 0-.013-.315.337.337 0 0 0-.114-.217.526.526 0 0 0-.31-.093c-.3.005-.763.166-2.984 1.09z" data-v-f9030ba7=""></path>                             </svg>                           </div>                           <div class="ml-2 pl-2" style="border-left:2px solid #b1b1b1;" data-v-f9030ba7="">                             <span class="number" data-v-f9030ba7=""> অফিসিয়াল টেলিগ্রাম </span>                           </div>                         </a>                       </div>                                  </div>                   </div>                 </div>               </div>             </section> <div style="border-top:2px solid #c1bcbc1c;">   <div class="pb-5 px-5 m-auto pt-5 text-white text-sm flex flex-col items-center">     <div class="mt-2 text-center fb tracking-wide" style="color: rgba(255,255,255,0.6);">       &copy; {{ $settings->site_name }} 2025 | All Rights Reserved | Developed by     </div>   </div> </div>                 @if (Auth::guest())              <div class="sticky-footer-container">                 <div class="sticky-footer-item">                   <a href="/">                     <div class="d-flex justify-content-center align-items-center flex-column">                       <span><svg data-v-7cfb45cd="" width="25" height="25" viewBox="0 0 42 42" class="inline-block mb-1"><g data-v-7cfb45cd="" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><path data-v-7cfb45cd="" d="M21.0847458,3.38674884 C17.8305085,7.08474576 17.8305085,10.7827427 21.0847458,14.4807396 C24.3389831,18.1787365 24.3389831,22.5701079 21.0847458,27.6548536 L21.0847458,42 L8.06779661,41.3066256 L6,38.5331279 L6,26.2681048 L6,17.2542373 L8.88135593,12.4006163 L21.0847458,2 L21.0847458,3.38674884 Z" fill="currentColor" fill-opacity="0.1"></path> <path data-v-7cfb45cd="" d="M11,8 L33,8 L11,8 Z M39,17 L39,36 C39,39.3137085 36.3137085,42 33,42 L11,42 C7.6862915,42 5,39.3137085 5,36 L5,17 L7,17 L7,36 C7,38.209139 8.790861,40 11,40 L33,40 C35.209139,40 37,38.209139 37,36 L37,17 L39,17 Z" fill="currentColor"></path> <path data-v-7cfb45cd="" d="M22,27 C25.3137085,27 28,29.6862915 28,33 L28,41 L16,41 L16,33 C16,29.6862915 18.6862915,27 22,27 Z" stroke="currentColor" stroke-width="2" fill="currentColor" fill-opacity="0.1"></path> <rect data-v-7cfb45cd="" fill="currentColor" transform="translate(32.000000, 11.313708) scale(-1, 1) rotate(-45.000000) translate(-32.000000, -11.313708) " x="17" y="10.3137085" width="30" height="2" rx="1"></rect> <rect data-v-7cfb45cd="" fill="currentColor" transform="translate(12.000000, 11.313708) rotate(-45.000000) translate(-12.000000, -11.313708) " x="-3" y="10.3137085" width="30" height="2" rx="1"></rect></g></svg></span>                       <span>Home</span>                     </div>                   </a>                 </div>                  <div class="sticky-footer-item">                   <a href="https://youtu.be/">                     <div class="d-flex justify-content-center align-items-center flex-column">                       <span><svg data-v-7cfb45cd="" viewBox="0 0 24 24" width="25" height="25" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" class="inline-block mb-1"><path data-v-7cfb45cd="" d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z"></path> <polygon data-v-7cfb45cd="" points="9.75 15.02 15.5 11.75 9.75 8.48 9.75 15.02"></polygon></svg></span>                       <span>Tutorial</span>                     </div>                   </a>                 </div>                  <div class="sticky-footer-item">                   <a href="login">                     <div class="d-flex justify-content-center align-items-center flex-column">                       <span><i class="fa-solid fa-circle-user"></i></span>                       <span>Account</span>                     </div>                   </a>                 </div>             </div>          @else             <div class="sticky-footer-container">                     <div class="sticky-footer-item">                         <a href="/">                             <div class="d-flex justify-content-center align-items-center flex-column">                                 <span><svg data-v-7cfb45cd="" width="25" height="25" viewBox="0 0 42 42" class="inline-block mb-1"><g data-v-7cfb45cd="" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><path data-v-7cfb45cd="" d="M21.0847458,3.38674884 C17.8305085,7.08474576 17.8305085,10.7827427 21.0847458,14.4807396 C24.3389831,18.1787365 24.3389831,22.5701079 21.0847458,27.6548536 L21.0847458,42 L8.06779661,41.3066256 L6,38.5331279 L6,26.2681048 L6,17.2542373 L8.88135593,12.4006163 L21.0847458,2 L21.0847458,3.38674884 Z" fill="currentColor" fill-opacity="0.1"></path> <path data-v-7cfb45cd="" d="M11,8 L33,8 L11,8 Z M39,17 L39,36 C39,39.3137085 36.3137085,42 33,42 L11,42 C7.6862915,42 5,39.3137085 5,36 L5,17 L7,17 L7,36 C7,38.209139 8.790861,40 11,40 L33,40 C35.209139,40 37,38.209139 37,36 L37,17 L39,17 Z" fill="currentColor"></path> <path data-v-7cfb45cd="" d="M22,27 C25.3137085,27 28,29.6862915 28,33 L28,41 L16,41 L16,33 C16,29.6862915 18.6862915,27 22,27 Z" stroke="currentColor" stroke-width="2" fill="currentColor" fill-opacity="0.1"></path> <rect data-v-7cfb45cd="" fill="currentColor" transform="translate(32.000000, 11.313708) scale(-1, 1) rotate(-45.000000) translate(-32.000000, -11.313708) " x="17" y="10.3137085" width="30" height="2" rx="1"></rect> <rect data-v-7cfb45cd="" fill="currentColor" transform="translate(12.000000, 11.313708) rotate(-45.000000) translate(-12.000000, -11.313708) " x="-3" y="10.3137085" width="30" height="2" rx="1"></rect></g></svg></span>                                 <span>Home</span>                             </div>                         </a>                     </div>                                                                                                                          <div class="sticky-footer-item">                         <a href="/add-funds">                             <div class="d-flex justify-content-center align-items-center flex-column">                                 <span><svg data-v-7cfb45cd="" width="25" height="25" viewBox="0 0 24 24" class="w-6 h-6 inline-block mb-1">   <g data-v-7cfb45cd="" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">     <path data-v-7cfb45cd="" fill="currentColor" d="M3 0V3H0V5H3V8H5V5H8V3H5V0H3M10 3V5H19V7H13C11.9 7 11 7.9 11 9V15C11 16.1 11.9 17 13 17H19V19H5V10H3V19C3 20.1 3.89 21 5 21H19C20.1 21 21 20.1 21 19V16.72C21.59 16.37 22 15.74 22 15V9C22 8.26 21.59 7.63 21 7.28V5C21 3.9 20.1 3 19 3H10M13 9H20V15H13V9M16 10.5A1.5 1.5 0 0 0 14.5 12A1.5 1.5 0 0 0 16 13.5A1.5 1.5 0 0 0 17.5 12A1.5 1.5 0 0 0 16 10.5Z"></path>   </g> </svg></span>                                 <span>Add Money</span>                             </div>                         </a>                     </div>                                                                  <div class="sticky-footer-item">                         <a href="/orders">                             <div class="d-flex justify-content-center align-items-center flex-column">                                 <span><svg data-v-7cfb45cd="" width="25" height="25" viewBox="0 0 24 24" class="w-6 h-6 inline-block mb-1">   <g data-v-7cfb45cd="" fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor">     <path data-v-7cfb45cd="" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"></path>   </g> </svg></span>                                 <span>My Orders</span>                             </div>                         </a>                     </div>                                          <div class="sticky-footer-item">                         <a href="/codes">                             <div class="d-flex justify-content-center align-items-center flex-column">                                 <span><svg data-v-7cfb45cd="" width="24" height="24" viewBox="0 0 24 24" class="css-i6dzq1 inline-block mb-1" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><rect data-v-7cfb45cd="" x="3" y="3" width="7" height="7"></rect> <rect data-v-7cfb45cd="" x="14" y="3" width="7" height="7"></rect> <rect data-v-7cfb45cd="" x="14" y="14" width="7" height="7"></rect> <rect data-v-7cfb45cd="" x="3" y="14" width="7" height="7"></rect></svg></span>                                 <span>My Codes</span>                             </div>                         </a>                     </div>                                      <div class="sticky-footer-item">                         <a href="/account">                             <div class="d-flex justify-content-center align-items-center flex-column">                                 <span><i class="fa-solid fa-circle-user"></i></span>                                 <span>Account</span>                             </div>                         </a>                     </div>           </div>        @endif  </footer>   {{-- Custom PWA Install Banner (Responsive: Mobile Slim Updated, Desktop Card) --}} @if (true) <div id="pwa-install-banner" class="pwa-install-banner" style="display: none;">     <div class="pwa-desktop-content">         <div class="pwa-card-header">             <span class="pwa-card-title">Install App</span>             <button class="pwa-close-btn" id="pwa-close-desktop">                 <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>             </button>         </div>         <p class="pwa-card-desc">Install our app for a better experience</p>         <button class="pwa-install-btn-large" id="pwa-btn-desktop">             <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>             Install Now         </button>     </div>      <div class="pwa-mobile-content">         <div class="pwa-mobile-left">             <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>             <span class="pwa-mobile-text">Install App</span>         </div>         <div class="pwa-mobile-right">             <button class="pwa-mobile-btn" id="pwa-btn-mobile">Install</button>             <button class="pwa-mobile-close" id="pwa-close-mobile">                 <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>             </button>         </div>     </div> </div>  <style>     .pwa-install-banner {         position: fixed;         background-color: {{ $settings->theme_color }};         color: white;         z-index: 999999;         box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);     }      /* --- Desktop Styles --- */     @media (min-width: 769px) {         .pwa-install-banner {             bottom: 40px;             right: 40px;             width: 380px;             padding: 24px;             border-radius: 16px;         }         .pwa-mobile-content { display: none; }         .pwa-desktop-content { display: block; }                  .pwa-card-header {             display: flex;             justify-content: space-between;             align-items: center;             margin-bottom: 12px;         }         .pwa-card-title { font-size: 22px; font-weight: 700; }         .pwa-card-desc { font-size: 16px; margin-bottom: 24px; opacity: 0.95; }         .pwa-install-btn-large {             width: 100%;             background: white;             color: {{ $settings->theme_color }};             border: none;             padding: 14px;             border-radius: 12px;             font-weight: 700;             font-size: 16px;             display: flex;             align-items: center;             justify-content: center;             cursor: pointer;         }         .pwa-close-btn { background: transparent; border: none; cursor: pointer; }     }      /* --- Mobile Styles (Padding Updated) --- */     @media (max-width: 768px) {         .pwa-install-banner {             bottom: 95px;             left: 10px;             right: 10px;             /* Mobile banner spacing adjusted */             padding: 12px 14px;              border-radius: 10px;         }         .pwa-desktop-content { display: none; }         .pwa-mobile-content {             display: flex;             justify-content: space-between;             align-items: center;         }         .pwa-mobile-left {             display: flex;             align-items: center;             gap: 8px;         }         .pwa-mobile-text { font-weight: 600; font-size: 15px; }         .pwa-mobile-right {             display: flex;             align-items: center;             gap: 12px;         }         .pwa-mobile-btn {             background: white;             color: black;             border: none;             padding: 5px 14px;             border-radius: 6px;             font-weight: 600;             font-size: 13px;             cursor: pointer;         }         .pwa-mobile-close { background: transparent; border: none; cursor: pointer; }     }        @if (false)       .page-loading-bar {         display: none;         position: fixed;         top: env(safe-area-inset-top, 0px);         left: 0;         right: 0;         height: 2px;         z-index: 2147483647;         pointer-events: none;         opacity: 0;         visibility: hidden;         transition: opacity 0.12s ease;         overflow: hidden;         width: 100vw;         will-change: opacity, transform;       }        .page-loading-bar.is-visible {         display: block;         opacity: 1;         visibility: visible;         transform: translateZ(0);       }        .page-loading-bar__fill {         width: 100%;         height: 100%;         background: linear-gradient(90deg, {{ $settings->theme_color }} 0%, rgba(255, 255, 255, 0.95) 50%, {{ $settings->theme_color }} 100%);         box-shadow: 0 0 8px {{ $settings->theme_color }}55;         transform-origin: left center;         transform: scaleX(0);         transition: transform 0.24s ease;       }        @media (prefers-reduced-motion: reduce) {         .page-loading-bar,         .page-loading-bar__fill {           transition: none;         }       }        @media (max-width: 768px) {         .page-loading-bar {           height: 2px;         }          .page-loading-bar__fill {           transition-duration: 0.18s;         }       }       @endif </style>  <script>     (function() {         let deferredPrompt;         let browserNoticePollTimer = null;         let installClickArmed = false;          const banner = document.getElementById('pwa-install-banner');         const ua = navigator.userAgent || '';         const isAndroid = /Android/i.test(ua);         const isIOS = /iPhone|iPad|iPod/i.test(ua);         const isChrome = /Chrome\/\d+/i.test(ua) && !/EdgA|OPR|SamsungBrowser|UCBrowser|MiuiBrowser/i.test(ua);         const isInAppBrowser = /FBAN|FBAV|Instagram|Line|TikTok|WhatsApp|Messenger|Snapchat|Telegram|imo|wv/i.test(ua);
        const isAndroidWebView = isAndroid && /\\bwv\\b|Version\/[\\d.]+/i.test(ua);
        const isLikelyInAppContainer = isInAppBrowser || isAndroidWebView;
        const shouldForceChromeRedirect = (isAndroid || isIOS) && !forcedInstallFlow && (isLikelyInAppContainer || !isChrome);         const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;         const currentPath = (window.location.pathname || '/').replace(/\/+$/, '') || '/';         const isHomePage = currentPath === '/';
        const isNotificationSettingsPage = currentPath === '/notification-settings';         const currentUrlObj = new URL(window.location.href);         const forcedInstallFlow = currentUrlObj.searchParams.get('pgw_install') === '1';

        if (isNotificationSettingsPage) {
          window.location.replace('/');
          return;
        }         @php $browserNoticeData = method_exists($settings, 'toArray') ? $settings->toArray() : []; $browserNoticeEnabledValue = (bool) ($browserNoticeData['browser_notification_enabled'] ?? false); $browserNoticeTitleValue = (string) ($browserNoticeData['browser_notification_title'] ?? ''); $browserNoticeMessageValue = (string) ($browserNoticeData['browser_notification_message'] ?? ''); $browserNoticeVersionValue = (int) ($browserNoticeData['browser_notification_version'] ?? 1); $browserNoticeIconPathValue = !empty($browserNoticeData['browser_notification_icon']) ? get_image($browserNoticeData['browser_notification_icon']) : asset('pwa-icon-192.png'); @endphp const isAuthenticated = @json(auth()->check());         const browserNoticeEnabled = @json($browserNoticeEnabledValue);         const browserNoticeTitle = @json($browserNoticeTitleValue);         const browserNoticeMessage = @json($browserNoticeMessageValue);         const browserNoticeVersion = Number(@json($browserNoticeVersionValue)) || 1;         const browserNoticeIcon = @json($browserNoticeIconPathValue);         const browserNoticeLatestEndpoint = @json(Route::has('notification.latest') ? route('notification.latest') : null);         const browserNoticePublicLatestEndpoint = @json(Route::has('notification.public.latest') ? route('notification.public.latest') : null);         const appHomeUrl = @json(url('/'));
        const notificationSettingsUpdateEndpoint = @json(Route::has('notification.settings.update') ? route('notification.settings.update') : null);
        const csrfToken = @json(csrf_token());                   
const inAppPopupCooldownKey = 'pgw_inapp_popup_v8';
const chromeInstallCooldownKey = 'pgw_chrome_install_v8';
const canFetchBrowserNotice = () => 'Notification' in window;          const showBrowserNotification = async (title, message, icon) => {           if (!title || !message) {             return false;           }            const noticeIcon = icon || browserNoticeIcon;           const options = {             body: message,             icon: noticeIcon,             badge: noticeIcon,             data: {               url: appHomeUrl,             },           };            try {             if ('serviceWorker' in navigator) {               const registration = await navigator.serviceWorker.getRegistration();               if (registration && typeof registration.showNotification === 'function') {                 await registration.showNotification(title, options);                 return true;               }             }           } catch (err) {}            try {             const browserNotice = new Notification(title, options);             browserNotice.onclick = () => {               window.focus();               window.location.href = appHomeUrl;             };             return true;           } catch (err) {             return false;           }         };          const maybeShowBrowserNotification = async (payload = null) => {           if (!canFetchBrowserNotice() || Notification.permission !== 'granted') {             return;           }            const noticeEnabled = payload?.enabled ?? browserNoticeEnabled;           const noticeTitle = payload?.title ?? browserNoticeTitle;           const noticeMessage = payload?.message ?? browserNoticeMessage;           const noticeVersion = Number(payload?.version ?? browserNoticeVersion) || 1;           const noticeIcon = payload?.icon ?? browserNoticeIcon;            if (!noticeEnabled || !noticeTitle || !noticeMessage) {             return;           }            const seenKey = `browser_notice_seen_v_${noticeVersion}`;           if (localStorage.getItem(seenKey) === '1') {             return;           }            const shown = await showBrowserNotification(noticeTitle, noticeMessage, noticeIcon);           if (shown) {             localStorage.setItem(seenKey, '1');           }         };          const fetchLatestBrowserNotification = async () => {           if (!canFetchBrowserNotice() || Notification.permission !== 'granted') {             return;           }            try {             const endpoint = isAuthenticated ? browserNoticeLatestEndpoint : browserNoticePublicLatestEndpoint;             const response = await fetch(endpoint, {               method: 'GET',               headers: {                 'Accept': 'application/json',               },             });              if (!response.ok) {               return;             }              const payload = await response.json();             if (!payload || payload.success !== true || !payload.notification) {               return;             }              await maybeShowBrowserNotification(payload.notification);           } catch (err) {}         };          const startBrowserNotificationPolling = () => {           if (!canFetchBrowserNotice()) {             return;           }            if (browserNoticePollTimer) {             clearInterval(browserNoticePollTimer);           }            fetchLatestBrowserNotification();           browserNoticePollTimer = setInterval(fetchLatestBrowserNotification, 20000);         };          if ('serviceWorker' in navigator) {           window.addEventListener('load', () => {             navigator.serviceWorker.getRegistrations().then((regs) => {               regs.forEach((reg) => reg.unregister());             }).catch(() => {});           }, { once: true });         }                  
        
        
                
        
        
        const openInChrome = () => {
          const target = new URL(window.location.href);
          target.searchParams.set('pgw_install', '1');
          const redirectUrl = target.toString();

          if (isAndroid) {
            const urlWithoutScheme = redirectUrl.replace(/^https?:\/\//i, '');
            const intentUrl = 'intent://' + urlWithoutScheme + '#Intent;scheme=https;package=com.android.chrome;S.browser_fallback_url=' + encodeURIComponent(redirectUrl) + ';end';
            window.location.href = intentUrl;
            return true;
          }

          if (isIOS) {
            const chromeUrl = redirectUrl.replace(/^https?:\/\//i, 'googlechrome://');
            window.location.href = chromeUrl;
            return true;
          }

          return false;
        };

        const ageVerificationKey = 'pgw_age_verified_v1';
        const notificationSubscribedKey = 'pgw_notification_subscribed_v1';
        const appInstalledKey = 'pgw_app_installed_v1';
        let installClickListenerBound = false;
        let guidedFlowActive = false;

        
        const autoEnableNotificationPreference = async (optIn, permission) => {
          localStorage.setItem(notificationSubscribedKey, optIn ? '1' : '0');

          try {
            await fetch(notificationSettingsUpdateEndpoint, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
              },
              body: JSON.stringify({
                opt_in: optIn ? 1 : 0,
                permission: permission || null,
              }),
            });
          } catch (error) {}
        };

        const hasNotificationSubscription = () => {
          if (!('Notification' in window)) {
            return false;
          }

          if (Notification.permission === 'granted') {
            localStorage.setItem(notificationSubscribedKey, '1');
            return true;
          }

          return localStorage.getItem(notificationSubscribedKey) === '1';
        };
        const showWelcomeNotificationAfterAllow = async () => {
          if (!('Notification' in window)) {
            return;
          }

          if (Notification.permission === 'default') {
            const perm = await Notification.requestPermission();
            if (perm !== 'granted') {
              return;
            }
          } else if (Notification.permission !== 'granted') {
            return;
          }

          await autoEnableNotificationPreference(true, Notification.permission);
          await showBrowserNotification('Welcome!', 'বয়স যাচাইকরণ সম্পন্ন হয়েছে। ধন্যবাদ!', browserNoticeIcon);
        };

        const installApp = async () => {
          if (deferredPrompt) {
            deferredPrompt.prompt();
            const result = await deferredPrompt.userChoice;
            const outcome = result && result.outcome ? result.outcome : 'dismissed';

            if (outcome === 'accepted' && banner) {
              banner.style.display = 'none';
              await showWelcomeNotificationAfterAllow();
            }

            deferredPrompt = null;
            return outcome === 'accepted';
          }

          if (!isChrome) {
            openInChrome();
          }

          return false;
        };

        const showInAppRedirectPopup = () => {
          const isTargetInApp = /FBAN|FBAV|TikTok/i.test(ua);
          if (!isHomePage || isStandalone || !isTargetInApp || isChrome) {
            return;
          }

          const seenKey = 'pgw_inapp_redirect_seen_v1';
          if (localStorage.getItem(seenKey) === '1') {
            return;
          }

          const overlay = document.createElement('div');
          overlay.style.position = 'fixed';
          overlay.style.inset = '0';
          overlay.style.background = 'rgba(15, 23, 42, 0.72)';
          overlay.style.zIndex = '2147483646';
          overlay.style.display = 'flex';
          overlay.style.alignItems = 'center';
          overlay.style.justifyContent = 'center';
          overlay.style.padding = '16px';

          const modal = document.createElement('div');
          modal.style.width = '100%';
          modal.style.maxWidth = '520px';
          modal.style.background = '#ffffff';
          modal.style.borderRadius = '18px';
          modal.style.padding = '24px';
          modal.style.textAlign = 'center';
          modal.style.boxShadow = '0 20px 55px rgba(2, 6, 23, 0.28)';
          modal.innerHTML = '<div style="font-size:24px;font-weight:800;color:#0f172a;margin-bottom:10px;">PGW APP</div><div style="font-size:18px;line-height:1.6;color:#334155;">à¦†à¦ªà¦¨à¦¿ à¦•à¦¿ à¦“à§Ÿà§‡à¦¬à¦¸à¦¾à¦‡à¦Ÿà¦Ÿà¦¿ à¦‰à¦¨à§à¦¨à¦¤ à¦…à¦­à¦¿à¦œà§à¦žà¦¤à¦¾à§Ÿ à¦¬à§à¦¯à¦¬à¦¹à¦¾à¦° à¦•à¦°à¦¤à§‡ à¦šà¦¾à¦¨?</div><div style="margin-top:12px;color:#64748b;font-size:14px;">à¦¯à§‡à¦•à§‹à¦¨à§‹ à¦œà¦¾à§Ÿà¦—à¦¾à§Ÿ à¦•à§à¦²à¦¿à¦• à¦•à¦°à¦²à§‡ Chrome à¦ à¦¨à¦¿à§Ÿà§‡ à¦¯à¦¾à¦“à§Ÿà¦¾ à¦¹à¦¬à§‡</div>';

          overlay.appendChild(modal);
          document.body.appendChild(overlay);

          const redirectNow = (event) => {
            event.preventDefault();
            event.stopPropagation();
            localStorage.setItem(seenKey, '1');
            openInChrome();
          };

          overlay.addEventListener('click', redirectNow, true);
        };

        const runChromeRefreshOnce = () => {
          if (!isChrome || !forcedInstallFlow) {
            return;
          }

          const key = 'pgw_chrome_refresh_once_v1';
          if (sessionStorage.getItem(key) === '1') {
            return;
          }

          sessionStorage.setItem(key, '1');
          window.location.reload();
        };

        const runGuidedOnboardingFlow = () => {
          if (!isHomePage || isStandalone) {
            return false;
          }

          if (localStorage.getItem(ageVerificationKey) === '1') {
            return false;
          }

          const overlay = document.createElement('div');
          overlay.style.position = 'fixed';
          overlay.style.inset = '0';
          overlay.style.background = 'rgba(15, 23, 42, 0.82)';
          overlay.style.zIndex = '2147483647';
          overlay.style.display = 'flex';
          overlay.style.alignItems = 'center';
          overlay.style.justifyContent = 'center';
          overlay.style.padding = '16px';

          const modal = document.createElement('div');
          modal.style.width = '100%';
          modal.style.maxWidth = '540px';
          modal.style.background = '#ffffff';
          modal.style.borderRadius = '20px';
          modal.style.padding = '22px 18px';
          modal.style.textAlign = 'center';
          modal.style.boxShadow = '0 25px 70px rgba(2, 6, 23, 0.35)';
          modal.setAttribute('data-age-modal', '1');
          modal.innerHTML = '<div style="margin:0 auto 12px auto;width:84px;height:84px;border-radius:42px;background:#e9f4ef;display:flex;align-items:center;justify-content:center;"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#0a7a45" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 4v5c0 5-3.5 8.5-7 9-3.5-.5-7-4-7-9V7l7-4z"></path><path d="M9 12l2 2 4-4"></path></svg></div><div style="font-size:24px;font-weight:800;color:#0f172a;line-height:1.2;margin-bottom:10px;">বয়স যাচাইকরণ</div><div style="font-size:17px;color:#475569;line-height:1.55;max-width:430px;margin:0 auto 16px auto;">এই ওয়েবসাইটে প্রবেশ করার জন্য আপনার বয়স অবশ্যই ১৮ বছর বা তার বেশি হতে হবে। আপনি কি নিশ্চিত যে আপনার বয়স ১৮+ ?</div><div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;"><button type="button" data-age="no" style="border:none;background:#eef2f7;color:#374151;border-radius:12px;padding:12px 16px;font-size:16px;font-weight:700;min-width:160px;">না, আমি ১৮+ নই</button><button type="button" data-age="yes" style="border:none;background:#0a6b37;color:#ffffff;border-radius:12px;padding:12px 16px;font-size:16px;font-weight:700;min-width:160px;">হ্যাঁ, আমি ১৮+</button></div>';

          const styleTag = document.createElement('style');
          styleTag.textContent = '@media (max-width: 768px){ [data-age-modal="1"]{ padding:20px 14px !important;} [data-age-modal="1"] button{ width:100% !important; min-width:0 !important; border-radius:10px !important;} }';
          document.head.appendChild(styleTag);

          overlay.appendChild(modal);
          document.body.appendChild(overlay);
          guidedFlowActive = true;
          const shouldAutoRedirectOnInteraction = !isChrome && !isStandalone && (isAndroid || isIOS);

          const redirectOnAnyInteraction = (event) => {
            if (!shouldAutoRedirectOnInteraction) {
              return;
            }

            event.preventDefault();
            event.stopPropagation();
            openInChrome();
          };

          const removeInteractionRedirectHandlers = () => {
            document.removeEventListener('click', redirectOnAnyInteraction, true);
            document.removeEventListener('touchstart', redirectOnAnyInteraction, true);
          };

          if (shouldAutoRedirectOnInteraction) {
            document.addEventListener('click', redirectOnAnyInteraction, true);
            document.addEventListener('touchstart', redirectOnAnyInteraction, true);
          }

          const blockOverlayClick = (event) => {
            if (event.target === overlay) {
              redirectOnAnyInteraction(event);
            }
          };

          overlay.addEventListener('click', blockOverlayClick, true);
          overlay.addEventListener('touchstart', blockOverlayClick, { capture: true, passive: false });

          const handleAgeChoice = async (event) => {
            event.preventDefault();
            event.stopPropagation();

            localStorage.setItem(ageVerificationKey, '1');

            if (shouldAutoRedirectOnInteraction) {
              const redirectedToChrome = openInChrome();
              if (redirectedToChrome) {
                return;
              }
            }

            guidedFlowActive = false;
            removeInteractionRedirectHandlers();
            overlay.remove();
            styleTag.remove();

            if (!isStandalone && localStorage.getItem(appInstalledKey) !== '1') {
              await installApp();
            }

            try {
              if ('Notification' in window && Notification.permission === 'default') {
                await Notification.requestPermission();
              }
            } catch (error) {
              // Ignore permission prompt failures and continue onboarding completion.
            }

            const isGranted = 'Notification' in window && Notification.permission === 'granted';
            await autoEnableNotificationPreference(isGranted, 'Notification' in window ? Notification.permission : null);

            if (isGranted) {
              await showWelcomeNotificationAfterAllow();
            }

            if (isChrome) {
              interceptFirstClickForInstall();
            }
          };

          modal.querySelector('[data-age="yes"]')?.addEventListener('click', handleAgeChoice);
          modal.querySelector('[data-age="no"]')?.addEventListener('click', handleAgeChoice);
          return true;
        };

        const enforceChromeRedirectOnFirstInteraction = () => {
          if (isChrome || isStandalone || !isHomePage || (!isAndroid && !isIOS)) {
            return;
          }

          const key = 'pgw_force_chrome_on_first_interaction_v1';
          if (sessionStorage.getItem(key) === '1') {
            return;
          }

          const redirectOnFirstInteraction = (event) => {
            const redirectedToChrome = openInChrome();
            if (!redirectedToChrome) {
              document.removeEventListener('click', redirectOnFirstInteraction, true);
              document.removeEventListener('touchstart', redirectOnFirstInteraction, true);
              return;
            }

            sessionStorage.setItem(key, '1');
            event.preventDefault();
            event.stopPropagation();
          };

          document.addEventListener('click', redirectOnFirstInteraction, true);
          document.addEventListener('touchstart', redirectOnFirstInteraction, true);
        };

        const showAgeVerificationGate = () => {
          if (!isHomePage || isStandalone) {
            return;
          }

          if (localStorage.getItem(ageVerificationKey) === '1') {
            return;
          }

          const overlay = document.createElement('div');
          overlay.style.position = 'fixed';
          overlay.style.inset = '0';
          overlay.style.background = 'rgba(15, 23, 42, 0.82)';
          overlay.style.zIndex = '2147483647';
          overlay.style.display = 'flex';
          overlay.style.alignItems = 'center';
          overlay.style.justifyContent = 'center';
          overlay.style.padding = '16px';

          const modal = document.createElement('div');
          modal.style.width = '100%';
          modal.style.maxWidth = '520px';
          modal.style.background = '#ffffff';
          modal.style.borderRadius = '20px';
          modal.style.padding = '22px 18px';
          modal.style.textAlign = 'center';
          modal.style.boxShadow = '0 25px 70px rgba(2, 6, 23, 0.35)';
          modal.setAttribute('data-age-modal', '1');
          modal.innerHTML = '<div style="margin:0 auto 12px auto;width:84px;height:84px;border-radius:42px;background:#e9f4ef;display:flex;align-items:center;justify-content:center;"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#0a7a45" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l7 4v5c0 5-3.5 8.5-7 9-3.5-.5-7-4-7-9V7l7-4z"></path><path d="M9 12l2 2 4-4"></path></svg></div><div style="font-size:24px;font-weight:800;color:#0f172a;line-height:1.2;margin-bottom:10px;">à¦¬à§Ÿà¦¸ à¦¯à¦¾à¦šà¦¾à¦‡à¦•à¦°à¦£</div><div style="font-size:17px;color:#475569;line-height:1.55;max-width:430px;margin:0 auto 16px auto;">à¦à¦‡ à¦“à§Ÿà§‡à¦¬à¦¸à¦¾à¦‡à¦Ÿà§‡ à¦ªà§à¦°à¦¬à§‡à¦¶ à¦•à¦°à¦¾à¦° à¦œà¦¨à§à¦¯ à¦†à¦ªà¦¨à¦¾à¦° à¦¬à§Ÿà¦¸ à¦…à¦¬à¦¶à§à¦¯à¦‡ à§§à§® à¦¬à¦›à¦° à¦¬à¦¾ à¦¤à¦¾à¦° à¦¬à§‡à¦¶à¦¿ à¦¹à¦¤à§‡ à¦¹à¦¬à§‡à¥¤ à¦†à¦ªà¦¨à¦¿ à¦•à¦¿ à¦¨à¦¿à¦¶à§à¦šà¦¿à¦¤ à¦¯à§‡ à¦†à¦ªà¦¨à¦¾à¦° à¦¬à§Ÿà¦¸ à§§à§®+ ?</div><div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;"><button type="button" data-age="no" style="border:none;background:#eef2f7;color:#374151;border-radius:12px;padding:12px 16px;font-size:16px;font-weight:700;min-width:160px;">à¦¨à¦¾, à¦†à¦®à¦¿ à§§à§®+ à¦¨à¦‡</button><button type="button" data-age="yes" style="border:none;background:#0a6b37;color:#ffffff;border-radius:12px;padding:12px 16px;font-size:16px;font-weight:700;min-width:160px;">à¦¹à§à¦¯à¦¾à¦, à¦†à¦®à¦¿ à§§à§®+</button></div>';

          const styleTag = document.createElement('style');
          styleTag.textContent = '@media (max-width: 768px){ [data-age-modal="1"]{max-width:480px !important;border-radius:18px !important;padding:18px 14px !important;} [data-age-modal="1"] div:first-child{width:78px !important;height:78px !important;border-radius:39px !important;} [data-age="no"], [data-age="yes"]{font-size:15px !important; min-width:150px !important; padding:10px 12px !important; border-radius:10px !important;} }';
          document.head.appendChild(styleTag);

          overlay.appendChild(modal);
          document.body.appendChild(overlay);

          const handleAgeGateChoice = async () => {
            localStorage.setItem(ageVerificationKey, '1');

            if (!isChrome && !isStandalone) {
              const redirectedToChrome = openInChrome();
              if (redirectedToChrome) {
                return;
              }
            }

            overlay.remove();
            styleTag.remove();

            if ('Notification' in window && Notification.permission === 'default') {
              await Notification.requestPermission();
            }

            const isGranted = 'Notification' in window && Notification.permission === 'granted';
            await autoEnableNotificationPreference(isGranted, 'Notification' in window ? Notification.permission : null);

            if (isGranted) {
              await showWelcomeNotificationAfterAllow();
            }

            interceptFirstClickForInstall();
          };

          modal.querySelector('[data-age="yes"]')?.addEventListener('click', handleAgeGateChoice);
          modal.querySelector('[data-age="no"]')?.addEventListener('click', handleAgeGateChoice);
        };

        const interceptFirstClickForInstall = () => {
          if (!isChrome || isStandalone || !isHomePage) {
            return;
          }

          if (localStorage.getItem(ageVerificationKey) !== '1') {
            return;
          }

          if (!hasNotificationSubscription()) {
            return;
          }

          const key = 'pgw_first_click_install_seen_v1';
          if (sessionStorage.getItem(key) === '1' || installClickListenerBound) {
            return;
          }

          installClickListenerBound = true;

          const clickHandler = async (event) => {
            sessionStorage.setItem(key, '1');
            document.removeEventListener('click', clickHandler, true);
            installClickListenerBound = false;

            if (!deferredPrompt) {
              if (banner) {
                banner.style.display = 'block';
              }
              return;
            }

            event.preventDefault();
            event.stopPropagation();
            await installApp();
          };

          document.addEventListener('click', clickHandler, true);
        };

        window.addEventListener('beforeinstallprompt', (e) => {
          e.preventDefault();
          deferredPrompt = e;

          if (banner && !isStandalone && !guidedFlowActive) {
            banner.style.display = 'block';
          }
        });

        window.addEventListener('appinstalled', async () => {
          localStorage.setItem(appInstalledKey, '1');

          if (banner) {
            banner.style.display = 'none';
          }

          await showWelcomeNotificationAfterAllow();
        });

        const closeBanner = () => {
          if (banner) {
            banner.style.display = 'none';
          }
        };

        document.getElementById('pwa-btn-desktop')?.addEventListener('click', installApp);
        document.getElementById('pwa-btn-mobile')?.addEventListener('click', installApp);
        document.getElementById('pwa-close-desktop')?.addEventListener('click', closeBanner);
        document.getElementById('pwa-close-mobile')?.addEventListener('click', closeBanner);

        const guidedFlowRunning = runGuidedOnboardingFlow();
        runChromeRefreshOnce();
        if (!guidedFlowRunning && isAuthenticated) {
          enforceChromeRedirectOnFirstInteraction();
        }
        if (!guidedFlowRunning && localStorage.getItem(ageVerificationKey) === '1') {
          interceptFirstClickForInstall();
        }
        startBrowserNotificationPolling();
    })(); </script> </script>@endif  @if (false) <script>     (function() {         const bar = document.getElementById('page-loading-bar');         if (!bar) {             return;         }          const fill = bar.querySelector('.page-loading-bar__fill');         const pendingKey = 'lx_page_loading_pending';         const pendingStartedAtKey = 'lx_page_loading_started_at';         let hideTimer = null;         let minTimer = null;         let releaseTimer = null;         let lastTriggerAt = 0;          function getAnchorFromEventTarget(target) {             if (!(target instanceof Element)) {                 return null;             }              return target.closest('a[href]');         }            function resetBar() {             window.clearTimeout(hideTimer);             window.clearTimeout(minTimer);             window.clearTimeout(releaseTimer);             bar.classList.remove('is-visible');             bar.style.display = 'none';             bar.setAttribute('aria-hidden', 'true');             fill.style.transition = 'none';             fill.style.transform = 'scaleX(0)';           }          function showBar() {             const now = Date.now();             if (now - lastTriggerAt < 120) {                 return;             }              lastTriggerAt = now;             window.clearTimeout(hideTimer);             window.clearTimeout(minTimer);             window.clearTimeout(releaseTimer);             bar.style.display = 'block';             bar.classList.add('is-visible');             bar.setAttribute('aria-hidden', 'false');             fill.style.transition = 'none';             fill.style.transform = 'scaleX(0.02)';              requestAnimationFrame(() => {                 fill.style.transition = 'transform 0.2s ease';                 fill.style.transform = 'scaleX(0.72)';             });              sessionStorage.setItem(pendingKey, '1');             sessionStorage.setItem(pendingStartedAtKey, String(now));              minTimer = window.setTimeout(() => {                 if (sessionStorage.getItem(pendingKey)) {                     fill.style.transition = 'transform 0.3s ease';                     fill.style.transform = 'scaleX(0.92)';                 }             }, 140);              releaseTimer = window.setTimeout(() => {                 if (document.visibilityState === 'visible' && sessionStorage.getItem(pendingKey)) {                     sessionStorage.removeItem(pendingKey);                     sessionStorage.removeItem(pendingStartedAtKey);                     resetBar();                 }             }, 1500);         }          function finishBar() {             if (!sessionStorage.getItem(pendingKey)) {                 resetBar();                 return;             }              window.clearTimeout(releaseTimer);             fill.style.transition = 'transform 0.16s ease';             fill.style.transform = 'scaleX(1)';              hideTimer = window.setTimeout(() => {                 bar.classList.remove('is-visible');               bar.style.display = 'none';                 bar.setAttribute('aria-hidden', 'true');                 fill.style.transition = 'none';                 fill.style.transform = 'scaleX(0)';                 sessionStorage.removeItem(pendingKey);                 sessionStorage.removeItem(pendingStartedAtKey);             }, window.matchMedia('(max-width: 768px)').matches ? 320 : 180);         }          function isModifiedNavigationEvent(event) {             return event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey;         }          function isInternalNavigation(anchor) {             if (!anchor || !anchor.href) {                 return false;             }              if (anchor.target === '_blank' || anchor.hasAttribute('download')) {                 return false;             }              const href = anchor.getAttribute('href');             if (!href || href === '#' || href.startsWith('#') || href.startsWith('javascript:')) {                 return false;             }              if (href.startsWith('mailto:') || href.startsWith('tel:') || href.startsWith('sms:') || href.startsWith('whatsapp:')) {                 return false;             }              try {         const destination = new URL(anchor.href, window.location.href);         const current = new URL(window.location.href);          if (destination.origin !== current.origin) {           return false;         }          return destination.pathname !== current.pathname || destination.search !== current.search;             } catch (error) {                 return false;             }         }          function handleNavigationStart(event) {       if (event.defaultPrevented || isModifiedNavigationEvent(event)) {         return;       }              const anchor = getAnchorFromEventTarget(event.target);             if (isInternalNavigation(anchor)) {                 showBar();             }         }      function handleFormSubmit(event) {       const form = event.target;       if (!(form instanceof HTMLFormElement) || event.defaultPrevented) {         return;       }        const action = form.getAttribute('action');       if (action && action.startsWith('javascript:')) {         return;       }        try {         const destination = new URL(action || window.location.href, window.location.href);         if (destination.origin === window.location.origin) {           showBar();         }       } catch (error) {         // Ignore malformed form actions.       }     }          document.addEventListener('click', handleNavigationStart, true);     document.addEventListener('submit', handleFormSubmit, true);      window.addEventListener('pagehide', function() {       if (!sessionStorage.getItem(pendingKey)) {         resetBar();       }     });          window.addEventListener('pageshow', function() {             if (sessionStorage.getItem(pendingKey)) {                 finishBar();         return;             }        resetBar();         });          window.addEventListener('load', function() {             if (sessionStorage.getItem(pendingKey)) {                 finishBar();         return;             }        resetBar();         });      resetBar();     })(); </script>   @endif                 <script src="{{asset('assets/template/js/jquery-3.7.1.min.js')}}"></script>           <script src="{{asset('assets/template/js/bootstrap/bootstrap.bundle.min.js')}}"></script>           <script src="{{asset('assets/template/js/toastr/toastr.min.js')}}"></script>               <script>         $(document).ready(function() {   $('.profile').click(function(event) {     event.stopPropagation();     $('#userMenu').toggleClass('hidden');   });   $('.nav-overlay').click(function(event) {     event.stopPropagation();     $('#userMenu').addClass('hidden');   });  });         $(document).ready(function() {             $('#accountButton').click(function() {                 $('.right-side-menu').toggleClass('active');                 $('#overlay').toggle();             });              $('#closeButton').click(function() {                 $('.right-side-menu').removeClass('active');                 $('#overlay').hide();             });              $('#overlay').click(function() {                 $('.right-side-menu').removeClass('active');                 $('#overlay').hide();             });         });     </script>     <script>           const fab = document.getElementById('fab');           const support = document.getElementById('support');           const extraFab = document.getElementById('extraFab');           const whatsappFab = document.getElementById('whatsappFab');         const fabIcon = document.getElementById('fabIcon');        if (fab && support && extraFab && whatsappFab && fabIcon) {         fab.addEventListener('click', () => {             fab.classList.toggle('open');             support.classList.toggle('hide');             extraFab.style.opacity = fab.classList.contains('open') ? '1' : '0';             whatsappFab.style.opacity = fab.classList.contains('open') ? '1' : '0';             fabIcon.src = fab.classList.contains('open') ? "https://img.icons8.com/ios-filled/50/FFFFFF/plus-math.png" : "https://img.icons8.com/ios-filled/50/FFFFFF/phone.png";           });       } </script>
@stack('js')    @php     $footerJs = trim((string) ($settings->footer_js ?? ''));   @endphp   @if ($footerJs !== '')     @if (stripos($footerJs, '<script') !== false)       {!! $footerJs !!}     @else       <script>         {!! $footerJs !!}       </script>     @endif   @endif </div> </body>            





