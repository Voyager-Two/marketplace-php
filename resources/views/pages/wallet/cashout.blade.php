@extends('layouts.default')

@section('title')
    Cashout &bullet; Wallet
@endsection

@section('content')

    <div class="row">

    @include('sections.menu', ['page' => $page, 'menu_links' => $menu_links])

    @foreach ($errors->all() as $error)
        {{ alert($error) }}
    @endforeach

    <div class="grid6 module float_right_override">

        <div class="module-header">

            <div class="module-title">Wallet &bullet; Cashout</div>

            <div class="module_btns_wrap">

            </div>

        </div>

        <div class="module-content">

            @if ($cashout_balance >= config('app.min_cashout'))

            <div id="wallet_cashout_wrap">

                <div>Select a cashout method:</div>

                <div class="wallet_payment_btns_wrap">

                <span class="hint--top" aria-label="PayPal">
                    <span
                            id="wallet_cashout_paypal_btn"
                            class="wallet_cashout_payment_btn wallet_payment_btn btn"
                            data-payment-method="{{config('app.paypal_cashout_tid')}}"
                    >
                        <i class="icon-paypal"></i>
                    </span>
                </span>

                <span class="hint--top" aria-label="Bitcoin">
                    <span
                            id="wallet_cashout_bitcoin_btn"
                            class="wallet_cashout_payment_btn wallet_payment_btn btn"
                            data-payment-method="{{config('app.bitcoin_cashout_tid')}}"
                    >
                        <i class="icon-bitcoin"></i>
                    </span>
                </span>

                </div>

                <form action="{{route('wallet.cashout_post')}}" method="POST">

                <input id="cashout_payment_method" type="hidden" name="cashout_method" value="1">

                <div class="wallet_cashout_bitcoin_wrap input-wrap hidden">
                    <label class="sell_item_sale_price_label" for="wallet_cashout_bitcoin_input">Bitcoin Address</label>
                    <input id="wallet_cashout_bitcoin_input" class="wallet_cashout_input" type="text" name="bitcoin_address" value="{{Auth::user()->getBitcoinAddress()}}" placeholder="Bitcoin Address">
                </div>

                <div class="wallet_cashout_paypal_wrap input-wrap hidden">
                    <label class="sell_item_sale_price_label" for="wallet_cashout_paypal_input">PayPal Email</label>
                    <input id="wallet_cashout_paypal_input" class="wallet_cashout_input" type="text" name="paypal_email" value="{{Auth::user()->getPayPalEmail()}}" placeholder="PayPal Email">
                </div>

                <div id="wallet_cashout_amount_wrap" class="last-input-wrap hidden">
                    <label class="sell_item_sale_price_label" for="wallet_cashout_amount">Amount</label>
                    <input id="wallet_cashout_amount" class="wallet_amount_input" name="amount" type="number" step="1" min="{{config('app.min_cashout')}}" max="{{$cashout_balance}}" value="{{$cashout_balance}}" placeholder="Amount">
                    <input class="btn btn-purple" type="submit" value="Submit">
                    <br>
                    <span class="wallet_cashout_available hint--right hint--long" aria-label="Your cashout balance. You can only cashout what you earn from sales.">
                        <span class="wallet_cashout_available">Available: {{priceOutput($cashout_balance)}}</span>
                    </span>
                    <br>
                    <span class="wallet_cashout_fee_wrap wallet_cashout_bitcoin_wrap hidden">
                        Fee: 0% &middot; The amount received will vary on the USD to BTC exchange rate at the time of payment.
                        <br>
                        5 minute cashouts coming soon!
                    </span>
                    <span class="wallet_cashout_fee_wrap wallet_cashout_paypal_wrap hidden hint--right hint--long"
                          aria-label="This fee will be charged by PayPal to the amount we send you.">
                        Fee: 2.9%+
                        <br>
                        5 minute cashouts coming soon!
                    </span>
                </div>

                <div id="wallet_verified_wrap" class="hidden">

                    @if ($id_verified == 0)

                        <div>
                            To cashout with Bitcoin, we need to first verify your identity.<br>
                        </div>

                        <a class="btn btn-purple" href="{{route('verify_id')}}">
                            Verify Identity <i class="icon-right-arrow btn_icon"></i>
                        </a>

                    @elseif ($id_verified == 2)

                        <div>We are reviewing your ID verification, thank you for your patience.</div>

                    @endif

                </div>

                {{csrf_field()}}

                </form>

            </form>

            @else

                Minimum cashout amount is $5.00.

            @endif

        </div>

    </div>

    </div>

@stop