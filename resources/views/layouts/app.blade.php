@php

    $page_name = $page_name ?? 'contact_us';
    $has_scrollspy = $has_scrollspy ?? false;
    $alt_menu = $alt_menu ?? '';

@endphp
@if (!isset($page_menu))
    {{ $page_menu = '' }}
@endif
@if (!isset($menu_category))
    {{ $menu_category = '' }}
@endif

@include('inc.function')

<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">


    <title>{{ setTitle($page_menu) }}</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('storage/img/favicon.ico') }}" />
    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">

    <!-- Styles -->
    @livewireStyles
    @include('inc.styles')
</head>

<body {{ $has_scrollspy ? scrollspy($scrollspy_offset) : '' }}
    class=" {{ $alt_menu ? 'alt-menu' : '' }} {{ $page_name ?? '' === 'error404' ? 'error404 text-center' : '' }} {{ $page_name ?? '' === 'error500' ? 'error500 text-center' : '' }} {{ $page_name ?? '' === 'error503' ? 'error503 text-center' : '' }} {{ $page_name ?? '' === 'maintenence' ? 'maintanence text-center' : '' }}">

    <!-- BEGIN LOADER -->
    <div id="load_screen">
        <div class="loader">
            <div class="loader-content">
                <div class="spinner-grow align-self-center"></div>
            </div>
        </div>
    </div>
    <!--  END LOADER -->

    @include('inc.navbar')

    <!--  BEGIN MAIN CONTAINER  -->
    <div class="main-container" id="container">

        <div class="overlay"></div>
        <div class="search-overlay"></div>

        @include('inc.sidebar')

        <!--  BEGIN CONTENT PART  -->
        <div id="content" class="main-content">
            {{ @$slot }}
            @yield('content')

            @if ($page_name ?? '' != 'account_settings')
                @include('inc.footer')
            @endif
        </div>
        <!--  END CONTENT PART  -->

    </div>
    <!-- END MAIN CONTAINER -->
    @livewireScripts
    @include('inc.scripts')
    @include('inc.blockui_template')
    @include('inc.toast')

    <script>
        $('.block-page').on('click', function() {
            $.blockUI({
                message: '<svg> ... </svg>',
                fadeIn: 800,
                timeout: 5000, //unblock after 2 seconds
                overlayCSS: {
                    backgroundColor: '#1b2024',
                    opacity: 0.8,
                    zIndex: 1200,
                    cursor: 'wait'
                },
                css: {
                    border: 0,
                    color: '#fff',
                    zIndex: 1201,
                    padding: 0,
                    backgroundColor: 'transparent'
                }
            });
        });
    </script>
</body>

</html>
