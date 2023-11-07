@extends('layouts.default')

@section('title')
    Add Funds &bullet; Wallet
@endsection

@section('content')

    <div class="row">

    @include('sections.menu', ['page' => $page, 'menu_links' => $menu_links])

    <div class="grid6 module float_right_override">

        <div class="module-header">

            <div class="module-title">Wallet &bullet; Add Funds</div>

            <div class="module_btns_wrap">

            </div>

        </div>

        <div class="module-content">

            <div id="wallet_add_funds_wrap">

                <div>Select a payment method:</div>

                <span id="wallet_user_data" data-email="{{Auth::user()->getEmail()}}" class="hidden"></span>

                <div class="wallet_payment_btns_wrap">

                    <span class="hint--top" aria-label="Pay with G2A">
                        <span
                                id="wallet_add_funds_g2a_btn"
                                class="wallet_add_funds_payment_btn wallet_payment_btn btn"
                                data-payment-method="{{config('app.g2a_funds_tid')}}"
                                data-min-amount="{{config('app.min_fund_g2a')}}"
                                data-max-amount="{{config('app.max_fund_g2a')}}"
                        >
                            <img src="/img/g2a_logo.png" />
                        </span>
                    </span>

                    <span class="hint--top" aria-label="Pay with PayPal">
                        <span
                                id="wallet_add_funds_paypal_btn"
                                class="wallet_add_funds_payment_btn wallet_payment_btn btn"
                                data-payment-method="{{config('app.paypal_funds_tid')}}"
                                data-min-amount="{{config('app.min_fund_paypal')}}"
                                data-max-amount="{{config('app.max_fund_paypal')}}"
                                data-id-verified="{{$id_verified}}"
                                data-paypal-linked="{{$paypal_linked}}"
                        >
                            <i class="icon-paypal"></i>
                        </span>
                    </span>

                    <span class="hint--top" aria-label="Pay with Bitcoin">
                        <span
                                id="wallet_add_funds_bitcoin_btn"
                                class="wallet_add_funds_payment_btn wallet_payment_btn btn"
                                data-payment-method="{{config('app.bitcoin_funds_tid')}}"
                                data-min-amount="{{config('app.min_fund_bitcoin')}}"
                                data-max-amount="{{config('app.max_fund_bitcoin')}}"
                        >
                            <i class="icon-bitcoin"></i>
                        </span>
                    </span>

                </div>

                <div id="wallet_verified_wrap"  class="last-input-wrap hidden">

                    @if ($id_verified == 0)

                        <div>
                            To pay with PayPal, we need to first verify your identity.<br>
                        </div>

                        <a class="btn btn-purple" href="{{route('verify_id')}}">
                            Verify Identity <i class="icon-right-arrow btn_icon"></i>
                        </a>

                    @elseif ($id_verified == 2)

                        <div>We are reviewing your ID verification, thank you for your patience.</div>

                    @else

                        @if (!$paypal_linked)

                            <div id="wallet_add_funds_paypal_link_wrap"  class="last-input-wrap">

                                <a class="btn btn-purple" href="{{$paypal_connect_link}}">
                                    Connect PayPal Account
                                </a>

                            </div>

                        @endif

                    @endif

                </div>

                <div id="wallet_add_funds_amount_wrap" class="last-input-wrap hidden">

                    <label class="sell_item_sale_price_label" for="wallet_fund_amount">Amount</label>

                    <input
                            class="wallet_amount_input"
                            name="amount"
                            type="number"
                            step="1"
                            placeholder="Amount"
                    >

                    <span id="wallet_add_funds_continue" class="btn btn-purple">Checkout</span>

                </div>

                <div class="wallet_add_funds_ajax_msg hidden"></div>

                <script src="https://bitpay.com/bitpay.js"></script>

            </div>

        </div>

    </div>

    </div>

@stop