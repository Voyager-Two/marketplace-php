@section('header_mid_btns')

    <a
        id="header_cart_btn"
        class="header_mid_btns hint--bottom"
        href="{{route('cart')}}"
        aria-label="Cart: {{isset($cart_total_cost) ? priceOutput($cart_total_cost) : '$0.00'}}"
        data-cart-total="{{isset($cart_total_cost) ? $cart_total_cost : '0.00'}}"
    >
        <div class="header_mid_btns_text">
            <i class="icon-cart"></i>&middot;
            <span id="cart_item_count">
                {{isset($cart_total_count) ? $cart_total_count : '0'}}
            </span>
        </div>
    </a>

    @if (Auth::check())

        <a class="header_mid_btns_sell_items header_mid_btns" href="{{route('sell')}}">
            <div class="header_mid_btns_text">Sell</div>
        </a>

    @endif

@stop