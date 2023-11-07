@if (!empty($itemsForSaleDisplay))

    @foreach($itemsForSaleDisplay as $item)

        <div class="col-3-fixed module">

            <div class="module-content module-content-rounded item_display_module_content {{$item['boost'] == 1 ? 'item_boost' : ''}}">

                <div class="item_display_module_wrap">

                    <a class="item_display_module_title" href="/sale/{{$item['id']}}">
                        {{$item['name']}}
                    </a>

                    @if ($item['name_color'] != '')
                        @php($item['name_color'] = 'color: #'.getNewNameColor($item['name_color']))
                    @endif

                    <div class="item_display_desc_wrap">
                        <span class="desc_wear_text">
                            @if ($item['exterior_title']) {{$item['exterior_title']}} &nbsp;&middot;&nbsp; @endif
                        </span>
                        <span title="Grade" style="{{$item['name_color']}}">
                            {{$item['grade']}}
                        </span>
                    </div>

                    <img class="item_display_module_img" src="https://steamcommunity-a.akamaihd.net/economy/image/{{$item['icon_url']}}/256fx256f">

                    @if (!empty($item['stickers']))

                        <div class="item_display_stickers_wrap">

                            @foreach ($item['stickers'] as $sticker)

                                <img class="item_display_sticker" src="{{$sticker['img']}}" title="{{$sticker['title']}}" />

                            @endforeach

                        </div>

                    @endif

                    <div class="item_display_module_details_wrap {{$item['display_wear_value'] == 1 ? 'extra_height' : ''}}">

                        <div class="item_display_price_wrap">

                            <div class="item_display_price" title="Sale Price">{{$item['price_display']}}</div>

                            @if ($item['percent_off'] != '')

                                <span class="item_display_discount" title="Save {{$item['percent_off']}}% compared to suggested price">
                                    {{$item['percent_off']}}% off
                                </span>

                            @endif

                        </div>

                        <div class="item_display_suggested_price_wrap">
                            <div class="item_display_suggested_price" title="Suggested Price">
                                SP: {{$item['suggested_price']}}
                            </div>
                        </div>

                        @if ($item['display_wear_value'])

                            <div class="item_display_float_value_wrap">
                                <div class="item_display_float_value" title="Wear: {{$item['wear_value']}}">{{$item['wear_value_percent']}}%</div>
                                <i class="icon-down-arrow" title="{{getExteriorTitle($item['exterior'])}}" style="left:{{$item['wear_value_percent']}}%"></i>

                                @for ($i = 1; $i <= 5; $i++)

                                    <span class="wear_value_bar_{{$i}} {{$item['exterior'] == $i ? 'wear_value_active' : ''}}" title="{{getExteriorTitle($i)}}"></span>

                                @endfor

                            </div>

                        @endif

                        <div class="item_display_btns_wrap">

                            <div class="item_display_ajax_msg"></div>

                            <span class="hint--top" aria-label="Search for similar items">
                                <a class="item_display_search btn btn-transparent"
                                   href="/search?name={{$item['name']}}@if ($item['exterior_title'])&exterior={{$item['exterior']}}@endif"
                                ><i class="icon-search"></i></a>
                            </span>

                            @if ($item['inspect_link'] != '')

                                <span class="hint--top" aria-label="Inspect In-game">
                                    <a class="item_display_inspect_btn btn btn-transparent" href="{{$item['inspect_link']}}"><i class="icon-info"></i></a>
                                </span>

                            @endif

                            @if  ($item['status'] == config('app.sale_active'))

                                @if (Auth::check() && (Auth::id() == $item['user_id']))

                                    @if ((strpos('home,search', Route::currentRouteName()) !== false))

                                        <span class="cart_not_active_label">Your Item</span>

                                    @else
                                        <span class="hint--top" aria-label="Cancel this sale">
                                            <span
                                                    class="cancel_sale_btn sale_item_page btn btn-red"
                                                    data-sale-id="{{$item['id']}}"
                                                    role="button" tabindex="0" aria-pressed="false"
                                            >Cancel</span>
                                        </span>
                                    @endif

                                @else

                                    <span
                                            class="remove_from_cart_btn btn btn-red {{in_array($item['id'], $cart_sale_ids) ? '' : 'hidden-override'}} {{(strpos('cart', Route::currentRouteName()) !== false) ? 'in_cart' : ''}}"
                                            data-sale-id="{{$item['id']}}" data-sale-price="{{$item['price']}}"
                                            title="Remove from Cart"
                                            role="button" tabindex="0" aria-pressed="false"
                                    >Remove</span>

                                    <span
                                            class="add_to_cart_btn btn btn-purple {{in_array($item['id'], $cart_sale_ids) ? 'hidden-override' : ''}}"
                                            data-sale-id="{{$item['id']}}" data-sale-price="{{$item['price']}}"
                                            role="button" tabindex="0" aria-pressed="false"
                                    >Add to Cart</span>

                                @endif

                            @elseif ($item['status'] == config('app.sale_sold'))

                                <span class="cart_not_active_label">Sold</span>

                            @elseif ($item['status'] == config('app.sale_cancelled'))

                                <span class="cart_not_active_label">Cancelled</span>

                            @endif

                            <img class="item_display_game_img"
                                 src="{{getGameIcon($app_id)}}"
                                 title="{{getGames()[$app_id]}}"
                            />

                        </div>

                    </div>

                </div>

            </div>

        </div>

    @endforeach

@else

    <div class="margin-top-12">
        {{alert($no_result_alert)}}
    </div>

@endif