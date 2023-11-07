<!DOCTYPE html>
<html lang="{{ config('app.locale') }}">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#1d1f25">
        <title>@yield('title') @yield('title_separator', config('app.title_separator')) {{config('app.name')}} @yield('title_after_separator') @yield('title_after')</title>
        <meta name="description" content="@yield('desc')">
        <link rel="icon" href="/favicon.png?v=19">
        <!-- <link rel="apple-touch-icon-precomposed" href="apple-touch-icon-precomposed.png" />-->
        <link rel="stylesheet" href="/css/main.css?v=1">
        <link rel="stylesheet" href="//fonts.googleapis.com/css?family=Roboto:400,700" data-no-instant>
        <link rel="stylesheet" href="//fonts.googleapis.com/css?family=Montserrat:500,700" data-no-instant>
    </head>

    <body id="{{Route::currentRouteName()}}" data-csrf-token="{{csrf_token()}}">

        <div id="wrapper">

            <header>
                @include('includes.header')
            </header>

            @if ((strpos('home,search,referral,cart,sell', Route::currentRouteName()) !== false))

            <div class="container container-full">

            @else

            <div class="container">

            @endif

                @include('sections.alert')
                @yield('content')

            </div>

                <div id="footer">
                    @include('includes.footer')
                </div>

                <script src="/js/static.js?v=1" data-instant-track></script>

                @if (App::environment('production'))
                    <script async src="/js/main.min.js?v=1" data-no-instant></script>
                @else
                    <script async src="/js/main.js?v=1" data-no-instant></script>
                @endif

            </div>

        </div>

    </body>

</html>
