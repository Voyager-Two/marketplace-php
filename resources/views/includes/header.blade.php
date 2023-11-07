@include('sections.header_mid_btns')

<div class="content">

    <a id="logo" href="/">
        <img src="/img/icon_40.png" />
        <span class="logo-text">Marketplace</span>
    </a>

    <nav class="nav-mobile left">

        <div class="nav-more dropdown">

            <span class="nav-mobile-menu dropdown-toggle" data-toggle="dropdown">
                <i class="icon-menu"></i>
            </span>

            <ul class="nav-dropdown-menu dropdown-menu">
                <li><a href="{{route('support')}}">Support</a></li>
                <li><a href="//discordapp.com/invite/K6vSTSC" target="_blank">Discord</a></li>
                <li><a href="//www.reddit.com/r/Marketplace/" target="_blank">Reddit</a></li>
                <li><a href="//twitter.com/_Marketplace" target="_blank">Twitter</a></li>
                {{--<li><a href="//www.facebook.com/MarketplaceLLC/" target="_blank">Facebook</a></li>--}}
                <li><a href="//steamcommunity.com/groups/Marketplace" target="_blank">Steam Group</a></li>
            </ul>

        </div>

    </nav>

    <nav class="nav-desktop left">

        <div class="nav-pick-game dropdown">

            <span class="dropdown-toggle" data-toggle="dropdown">
                Game: <b>{{getGameShortName(isset($app_id) ? $app_id : 730)}}</b> <i class="icon-down-arrow"></i>
            </span>

            <ul class="nav-dropdown-menu dropdown-menu">
                @include('sections.nav_game_select')
            </ul>

        </div>

        <a class="nav-support" href="{{route('support')}}">Support</a>

        <div class="nav-more dropdown">

            <span class="nav-a-more dropdown-toggle" data-toggle="dropdown">
               <i class="icon-dots"></i>
            </span>

            <ul class="nav-dropdown-menu dropdown-menu">

            </ul>

        </div>

    </nav>

    <div id="header_right_wrap">

        @if (Auth::check())

            {{-- signed in users --}}

            <div id="header_mid_btns_wrap">

                @yield('header_mid_btns')

                @if (Auth::user()->getShowWalletAmount())
                    <a class="header_mid_btns_acc_balance header_mid_btns hint--bottom" aria-label="Wallet" href="{{route('wallet')}}">
                        <div class="header_mid_btns_text">{{priceOutput(Auth::user()->wallet_balance->getBalance())}}</div>
                    </a>
                @else
                    <a class="header_mid_btns_acc_balance _no_show header_mid_btns" href="{{route('wallet')}}">
                        <div class="header_mid_btns_text">Wallet</div>
                    </a>
                @endif

            </div>

            <div id="header-btns" class="header_btn_dropdown">

                <div class="dropdown">

                    <span id="dropdown-toggle" data-toggle="dropdown" class='header_btn_username btn header-btn'>

                        @if (Auth::user()->getUnseenNotificationsCount() > 0)
                            <span class="header_notification_indicator">{{Auth::user()->getUnseenNotificationsCount()}}</span>
                        @endif

                        <img class="header_avatar" src="{{Auth::user()->getAvatar()}}">
                        <i id="header_down_arrow"></i>

                    </span>

                    <ul id="header_dashboard" class="nav-dropdown-menu dropdown-menu">

                        <div class="header_dashboard_top">

                            <div class="header_dashboard_name">
                                <span>{{Auth::user()->getUsername()}}</span>
                            </div>

                        </div>

                        <div class="header_dashboard_options">

                            <a href="{{route('notifications')}}">

                                @if (Auth::user()->getUnseenNotificationsCount() > 0)
                                    <span class="header_notification_indicator_2 yellow">{{Auth::user()->getUnseenNotificationsCount()}}</span>
                                @else
                                    <span class="header_notification_indicator_2">0</span>
                                @endif
                                Notifications

                            </a>

                            @if (Auth::user()->getShowWalletAmount())
                                <a class="header_dashboard_wallet_btn" href="{{route('wallet')}}">
                                    Wallet: {{priceOutput(Auth::user()->wallet_balance->getBalance())}}
                                </a>
                            @else
                                <a class="header_dashboard_wallet_btn" href="{{route('wallet')}}">
                                    Wallet
                                </a>
                            @endif

                            <a class="header_dashboard_sell_btn" href="{{route('sell')}}">Sell</a>

                            <a href="{{route('manage_sales')}}">Manage Sales</a>

                            @if (isStaff(Auth::user()->getGroupId()))
                                <a href="{{route('staff_panel')}}">Staff Panel</a>
                            @endif

                            <a href="{{route('settings')}}">Settings</a>

                            <form method="post" action='{{ route('auth.signout') }}'>
                                {{ csrf_field() }}
                                <a class="form_submit_btn">Sign out</a>
                            </form>

                        </div>

                    </ul>

                </div>

            </div>

        @else

            {{-- guests --}}

            <div id="header_mid_btns_wrap">
                @yield('header_mid_btns')
            </div>

            <div id="header-btns">
                <a data-no-instant id="signin_btn" href="{{ route('auth.signin') }}" class="btn header-btn header_btn_signin pos-relative">
                    <img class="header_btn_signin_steam_logo" src="/img/steam_logo.png?v=1" />
                    <span class="header-signin-btn-text1">SIGN IN</span>
                    <span class="header-signin-btn-text2">via <span>Steam</span></span>
                </a>
            </div>
        @endif

    </div>

</div>
