<div class="deposit_trade_offer_accept_wrap">

    <div>
        <div>Sale assigned to bot #<b>{{$trade_offer_bot_id}}</b></div>
        <div class="hint--top hint--long" aria-label="Please make sure this token matches the one received on the trade offer message.">Token: <b>{{$trade_offer_auth_token}}</b></div>
        <div class="deposit_trade_offer_accept_statement">Please accept our sale trade offer:</div>
    </div>

    <a class="deposit_trade_offer_open_browser btn btn-small btn-purple" href="https://steamcommunity.com/tradeoffer/{{$trade_id}}/" target="_blank">Open in Browser</a>
    or
    <a class="deposit_trade_offer_open_steam btn btn-small btn-purple" href="steam://url/ShowTradeOffer/{{$trade_id}}">Open in Steam Client</a>

</div>

<div class="module_content_divider_no_padding"></div>

<span class="deposit_trade_offer_bottom_text">
    The items you trade will be automatically listed for sale.
    <br>
    Offer will expire after 5 minutes. <a href="{{route('manage_sales')}}">Manage Sales</a>
</span>