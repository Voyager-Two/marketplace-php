@extends('layouts.default')

@section('title', 'Wallet')

@section('content')

    <div class="row">

    @include('sections.menu', ['page' => $page, 'menu_links' => $menu_links])

    <div class="grid6 module float_right_override">

        <div class="module-header">

            <div class="module-title">Wallet</div>

            <div class="module_btns_wrap">

            </div>

        </div>

        <div class="module-content">

            <div class="wallet_top_modules">

                <div class="display-inline-block">

                    <div class="wallet_module">
                        <span class="hint--top cursor-help" aria-label="Wallet Balance">
                            <span class="wallet_module_amount">{{priceOutput(Auth::user()->wallet_balance->getBalance())}}</span>
                        </span>
                        {{--<div class="wallet_module_balance_title">Wallet Balance</div>--}}
                        <a id="wallet_add_funds_btn" class="btn btn-purple" href="/wallet/add_funds">Add Funds</a>
                    </div>

                </div>

                <div class="display-inline-block">

                    <div class="wallet_module">
                        <span class="hint--top hint--long cursor-help" aria-label="Your cashout balance. You can only cashout what you earn from sales.">
                            <span class="wallet_module_amount _green">{{priceOutput($cashout_balance)}}</span>
                        </span>
                        {{--<div id="wallet_cashout_balance_text" class="wallet_module_balance_title">Cashout Balance</div>--}}
                        <a id="wallet_cashout_btn" class="btn btn-purple" href="/wallet/cashout">Cashout</a>
                    </div>


                </div>

            </div>

            <div class="wallet_vertical_divider"></div>

            <div class="wallet_right_info_wrap">

                @if ($bitpay_orders->count())

                    @foreach ($bitpay_orders as $bitpay_order)
                        <div class="wallet_right_info funds">{{priceOutput($bitpay_order->amount)}} funds pending confirmation (Bitcoin).</div>
                    @endforeach

                @endif

                @if ($cashout_requests->count())

                    @foreach ($cashout_requests as $cashout_request)

                        <div class="wallet_right_info cashout">

                            <span class="hint--top" aria-label="{{$cashout_request->send_address}}">
                                {{priceOutput($cashout_request->amount)}} cashout request pending ({{getCashoutMethodName($cashout_request->method)}}).
                            </span>

                            <form method="post" action="{{route('wallet.cashout_post')}}">
                                {{csrf_field()}}
                                <input type="hidden" name="cashout_request_id" value="{{$cashout_request->id}}">
                                <button class="hint--top wallet_cancel_cashout_btn button-no-style" aria-label="Cancel" type="submit" name="cancel_cashout" value="1">
                                    <i class="icon-x"></i>
                                </button>
                            </form>

                        </div>

                    @endforeach

                    {{--<div class="wallet_right_info funds">$100.00 funds purchase pending (PayPal).</div>--}}

                @endif

            </div>

        </div>

    </div>

    </div>

@stop
