@extends('layouts.default')

@section('title', 'Staff Panel')

@section('content')

    <div class="row">

    @include('sections.menu', ['page' => $page, 'menu_links' => $menu_links])

    <div class="grid6 module float_right_override">

        <div class="module-header">

            <div class="module-title">Staff Panel</div>

            <div class="module_btns_wrap">
                <a class="module_btn" href="https://discordapp.com/channels/320381092530225163/331267227196325891" target="_blank">
                    Discord Staff Chat
                </a>
            </div>

        </div>


        <div class="module-content">

            Team:
            @foreach ($staff_list as $staff)
                <a href="//steamcommunity.com/profiles/{{$staff->steam_id}}" target="_blank">{{$staff->username}}</a>@if (!$loop->last),@endif
            @endforeach

            <br>

            <div class="staff_cp_stats_block">
                <h4 class="purple3">Site Stats</h4><br>
                <span>&bullet; {{$total_users_count}} users</span><br>
                <span>&bullet; {{$total_items_on_sale}} items on sale</span><br>
                <span>&bullet; {{$total_items_purchased}} items purchased</span><br>
            </div>

            <div class="staff_cp_stats_block">
                <h4 class="purple3">Wallet Stats</h4><br>
                <span>&bullet; {{priceOutput($total_wallet_balance)}} in balances</span><br>
                <span>&bullet; {{priceOutput($total_cashout_balance)}} in cashout balances</span><br>
                <span>&bullet; {{priceOutput($total_purchased_funds)}} total added funds</span><br>
                <span>&bullet; {{priceOutput($total_pending_cashout)}} pending cashout</span><br>
                <span>&bullet; {{priceOutput($total_cashed_out)}} cashed out</span><br>
            </div>

            <div class="staff_cp_stats_block">
                <h4 class="purple3">Financial Status</h4><br>
                <span>&bullet; {{priceOutput($total_sale_commissions + $total_sale_option_purchases)}} in total net sales</span><br>
                <span>&bullet; {{priceOutput($total_sale_commissions)}} earned from sale fees</span><br>
                <span>&bullet; {{priceOutput($total_sale_option_purchases)}} earned from sale options</span><br>

                @if ($cash_flow_positive)
                    <span class="green">+ {{priceOutput($cash_flow)}} cash flow</span><br>
                @else
                    <span class="red">- {{priceOutput(abs($cash_flow))}} cash flow</span><br>
                @endif
            </div>

            <div class="display-inline-block font-size-13">
                <div class="module_content_divider_1px"></div>
                All information shown on the Staff Panel is the property of {{config('app.legal_name')}}; this information is considered strictly confidential and may not be shared with anyone outside of Marketplace staff.
                You may not share access to the Staff Panel or your Steam account. You will be held responsible for any unauthorized access to the Staff Panel
                via your account.
            </div>

        </div>

    </div>

    </div>

@stop
