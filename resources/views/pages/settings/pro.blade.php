@extends('layouts.default')

@section('title')
    Pro Subscription &bullet; Settings
@stop

@section('content')

    <div class="row">

    @include('sections.menu', ['page' => $page, 'menu_links' => $menu_links])

    <div class="grid6 module float_right_override">

        <div class="module-header">

            <div class="module-title">Pro Subscription</div>

            <div class="module_btns_wrap">

            </div>

        </div>

        <div class="module-content">

            @if (Auth::user()->getGroupId() != config('app.pro_gid'))

                <div>You have a standard account, which has a {{config('app.standard_sale_fee')}}% sale fee.</div>

                @if (Auth::user()->wallet_balance->getBalance() < config('app.pro_price'))
                    <span>You do not have enough funds to upgrade.</span>
                @endif

                <div>
                    <h1 class="purple3">Upgrade to Pro</h1><br>
                    &bullet; {{config('app.pro_sale_fee')}}% sale fee<br>
                    &bullet; ${{config('app.pro_price')}} for 30 days<br>
                    &bullet; <span class="font-size-13">More coming soon</span><br>
                    <form class="upgrade_to_pro_wrap" action="{{route('settings.pro')}}" method="POST">
                        <input type="hidden" name="pro_auto_renew" value="0">
                        <input id="pro_auto_renew" type="checkbox" name="pro_auto_renew" value="1">
                        <label class="checkbox_label hint--top" for="pro_auto_renew" aria-label="You will be automatically charged every 30 days.">Auto Renew</label>
                        {{csrf_field()}}
                        <button class="upgrade_to_pro_sub_btn btn btn-purple" type="submit">Upgrade</button>
                    </form>
                </div>

            @else

                @if ($auto_renew == 1)

                    <span>Your subscription will auto-renew on {{$end_time_output}}.</span>
                    &nbsp;
                    <form action="{{route('settings.pro')}}" method="POST">
                        {{csrf_field()}}
                        <input type="hidden" name="disable_auto_renew" value="1">
                        <button class="btn btn-purple btn-small" type="submit">Disable Auto Renew</button>
                    </form>

                @else

                    <span>Your subscription will end on {{$end_time_output}}.</span>
                    &nbsp;
                    <form action="{{route('settings.pro')}}" method="POST">
                        {{csrf_field()}}
                        <input type="hidden" name="enable_auto_renew" value="1">
                        <button class="btn btn-purple btn-small" type="submit">Enable Auto Renew</button>
                    </form>

                @endif

                <br>
                <h1 class="purple3">Benefits</h1><br>
                &bullet; {{config('app.pro_sale_fee')}}% sale fee<br>
                &bullet; <span class="font-size-13">More coming soon</span><br>

            @endif

        </div>

    </div>

    </div>
@stop