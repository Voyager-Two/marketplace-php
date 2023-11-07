@extends('layouts.default')

@section('title', 'Sell')

@section('content')

    <div class="sell_page row">

    <div class="module steam_inventory_wrap_grid">

        <div class="module-header">
            <div class="module-title">
                Steam

                <span class="sell_game_btn dropdown">

                    <span class="dropdown-toggle" data-toggle="dropdown">
                        {{getGameShortName($app_id)}} <i class="icon-down-arrow"></i>
                    </span>

                    <ul class="nav-dropdown-menu dropdown-menu">
                        @include('sections.nav_game_select')
                    </ul>

                </span>

                Inventory
            </div>

            <div class="module_btns_wrap">

                <span class="hint--left" aria-label="Cached for 5 minutes.">
                    <span class="module_btn cursor-help">
                        <i class="module_btn_icon_big_no_text icon-info"></i>
                    </span>
                </span>

            </div>
        </div>

        <div class="module-content no-padding">
            <input id="steam_inventory_search" class="ready_input_focus" type="text" name="search" placeholder="Search" autofocus>
            <div id="steam_inventory_wrap" class="prevent-parent-scroll">
                <div class="steam_inventory_center_text">Loading<span class="loading-dots"><span>.</span><span>.</span><span>.</span></span></div>
            </div>
        </div>

    </div>

    <div class="sell_item_choose_pricing_wrap module">

        <div class="module-header">
            <div id="sell_item_title" class="module-title">Pick an Item to Sell</div>
        </div>

        <div class="module-content sell_item_module_content">

            <div class="sell_item_error_msg red hidden"></div>

            <div id="sell_item_choose_pricing">

                <div id="sell_item_title_grade" class="hidden" title="Grade"></div>

                <div class="sell_item_choose_pricing_top">

                    <div id="sell_item_img_wrap">

                        <img id="sell_item_img" class="sell_item_default_img" src="https://steamcommunity-a.akamaihd.net/economy/image/-9a81dlWLwJ2UUGcVs_nsVtzdOEdtWwKGZZLQHTxDZ7I56KU0Zwwo4NUX4oFJZEHLbXH5ApeO4YmlhxYQknCRvCo04DEVlxkKgpot7HxfDhjxszJemkV086jloKOhcj5Nr_Yg2YfvZcg0rmXrI2n31ex8ks9Zjz2JIKdcVA4ZArRqVm-wLzn1sC8uJnMwWwj5HcoJjKuZA/256fx256f" />

                        <div class="sell_item_inspect_btn_wrap">
                            <span class="hint--left" aria-label="Inspect In-game"><a class="sell_item_inspect_btn btn"><i class="icon-info"></i></a></span>
                        </div>

                        <div class="sell_item_stickers_wrap3"></div>

                    </div>

                </div>

                <div class="sell_item_module sell_item_suggested_price">
                    <span>Suggested Price</span><br>
                    <span id="suggested_price_amount" class="sell_item_module_text2">$0.00</span>
                </div>

                <div class="sell_item_choose_pricing_bottom">

                    <div class="sell_item_your_price_wrap">
                        <label class="sell_item_sale_price_label" for="sell_item_your_price">Sale Price</label>
                        <input id="sell_item_your_price" type="number" step="0.01" min="{{config('app.min_item_price')}}" max="{{config('app.max_item_price')}}" placeholder="Price">
                    </div>

                    <div id="sell_item_quantity_wrap">
                        <label class="sell_item_quantity_label" for="sell_item_quantity">Quantity</label>
                        <input id="sell_item_quantity" type="number" value="1" min="1" max="2">
                    </div>

                    <div class="sell_item_bottom_modules">

                        <div class="sell_item_module sell_item_you_get">
                            <span class="sell_item_module_text">You Get</span><br>
                            <span id="sell_item_you_get" class="sell_item_module_text2">$0.00</span>
                        </div>

                        @php
                            if (Auth::user()->getGroupId() == config('app.standard_gid')) {
                                $user_sale_fee = config('app.standard_sale_fee');
                            } else {
                                $user_sale_fee = config('app.pro_sale_fee');
                            }
                        @endphp

                        <div class="sell_item_module sell_item_fee" title="When sold, {{config('app.name')}} will take a {{$user_sale_fee}}% sale fee.">
                            <span class="sell_item_module_text">Fee ({{$user_sale_fee}}%)</span><br>
                            <span id="sell_item_fee" class="sell_item_module_text2" data-sale-fee="{{$user_sale_fee}}">$0.00</span>
                        </div>

                    </div>

                    <select id="sell_item_sale_options">
                        <option value="0" selected="">Sale options</option>
                        <option value="boost">Promote: ${{config('app.boost_price')}}</option>
                        {{--<option value="private_1hr">Private [1 hr]: $0.10</option>
                        <option value="private_24hr">Private [24 hrs]: $0.30</option>
                        <option value="private_1wk">Private [7 days]: $1.25</option>
                        --}}
                    </select>

                    <span class="hint--top hint--long" aria-label="Promote: item will be promoted on the front page as well as search results.">
                        <span class="link sell_item_sale_options_info">?</span>
                    </span>

                    <span class="sell_item_confirm_item_btn btn btn-purple"><i class="icon-add btn_icon"></i>&nbsp; Add to Sell List</span>

                </div>

            </div>

        </div>

    </div>

    <div class="sell_list_items_wrap module">
        <div class="module-header pos-relative">
            <div class="module-title">Sell List (<span class="sell_list_item_count">0</span>/30) &bullet; <span class="sell_list_items_total_value">$0.00</span></div>
            <button id="sell_list_deposit_btn" class="btn btn-purple hidden">Deposit <i class="icon-right-arrow btn_icon"></i></button>
            <button id="sell_list_processing_btn" class="btn btn-purple hidden" disabled>Processing<span class="loading-dots"><span>.</span><span>.</span><span>.</span></span></button>
        </div>

        <div class="module-content no-padding">

            <div class="sell_list_deposit_ajax_msg red hidden"></div>

            <form method="post" action="/sell" id="sell_list_wrap" class="scrollbar-main">
                <div class="sell_items_module_middle_text">Pick some items to sell.</div>
            </form>
        </div>
    </div>

    </div>

@stop
