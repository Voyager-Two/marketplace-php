@extends('layouts.default')

@section('title_separator', '')
@section('title_after', 'Buy & Sell CS:GO, Dota 2, H1Z1, PUBG skins & items')
@section('title_after_separator'){!! config('app.title_separator') !!}@stop

@section('desc', 'Buy & sell CS:GO, Dota 2, H1Z1, PUBG skins & items.')

@section('content')

    @if (session('disable_front_page_text') == null)

        <div class="front_page_text close_module_target">

            <div class="top_text">
                Buy & sell CS:GO, PUBG, & H1Z1 items
            </div>

            <div class="middle_text">
                🎉 Say Goodbye to Sale Fees 🎉
                <br>
                0% sale fee for everyone, forever.
            </div>

        </div>

    @endif

    <div class="text-align-center">

        <div class="change_game_btn dropdown">

            <div class="btn btn-black dropdown-toggle" data-toggle="dropdown">
                Game: <b>{{getGameShortName($app_id)}}</b> <i class="icon-down-arrow"></i>
            </div>

            <ul class="nav-dropdown-menu dropdown-menu dropdown-menu-right">
                @include('sections.nav_game_select')
            </ul>

        </div>

    </div>

    @include('sections.items_easy_nav')

    @include('sections.items_search')

    <div class="jscroll">

        <div class="row row-condensed row-centered">
            @include('sections.items_for_sale', ['no_result_alert' => 'No items listed for sale, check back later.'])
        </div>

        @if (count($itemsForSale) == 20)
            @include('includes.footer_autoload')
        @endif

        <div class="item_display_pagination_wrap hidden">
            {{ isset($itemsForSale) ? $itemsForSale->links() : '' }}
        </div>

    </div>

@stop
