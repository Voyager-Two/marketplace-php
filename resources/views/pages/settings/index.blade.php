@extends('layouts.default')

@section('title', 'Account Settings')

@section('content')

    <div class="row">

    <div class="col-10 center-block module">

        <div class="module-header">

            <div class="module-title">Account Settings</div>

            <div class="module_btns_wrap">

                <span class="hint--left hint--long" aria-label="Your Steam profile name/avatar is updated at sign-in.">
                    <span class="module_btn cursor-help">
                        <i class="module_btn_icon_big_no_text icon-info"></i>
                    </span>
                </span>

            </div>

        </div>

        <div class="module-content">

            <div class="left">

            <div class="module_section">

                <label class="module_label" for="change_email">Email</label>

                <div class="acc_settings_section_ajax_msg_wrap">
                    <div id="change_email_ajax_msg"></div>
                </div>

                <div class="module_section_input">
                    <input id="change_email" type="text" value="{{Auth::user()->getEmail()}}" placeholder="Contact Email">
                </div>

            </div>

            <br>

            <div class="module_section">

                <label class="module_label" for="change_trade_url">Trade URL</label>
                <span class="hint--top module_label_hint" aria-label="Click to access your Steam Trade URL">
                    <a class="module_label_link" href="https://steamcommunity.com/id/me/tradeoffers/privacy#trade_offer_access_url" target="_blank">[?]</a>
                </span>

                <div class="acc_settings_section_ajax_msg_wrap">
                    <div id="change_trade_url_ajax_msg"></div>
                </div>

                <div class="module_section_input">
                    <input id="change_trade_url" type="text" value="{{$trade_url}}" placeholder="Steam Trade URL">
                </div>

            </div>

            <br>

            <div class="module_section">

                <label class="module_label" for="change_time_zone">Time Zone</label>

                <div class="acc_settings_section_ajax_msg_wrap">
                    <div id="change_time_zone_ajax_msg"></div>
                </div>

                <div class="module_section_select">
                    <select id="change_time_zone" name="time_zone">
                        {!! $time_zone_options !!}
                    </select>
                </div>

            </div>

            </div>

            <div class="acc_settings_vertical_divider"></div>

            <div class="acc_settings_right_option">

                <label class="switch">
                    <input id="show_wallet_amount" type="checkbox" {{$show_wallet_amount ? 'checked' : ''}} data-balance="{{Auth::user()->wallet_balance->getBalance()}}">
                    <span class="slider"></span>
                </label>

                <span class="slider_loading"></span>
                <label id="show_wallet_amount_label" for="show_wallet_amount">Show wallet balance</label>

            </div>

            <div class="acc_settings_right_option">

                <label class="switch">
                    <input id="send_sales_receipts" type="checkbox" {{$send_sales_receipts ? 'checked' : ''}}>
                    <span class="slider"></span>
                </label>

                <span class="slider_loading"></span>
                <label id="send_sales_receipts_label" for="send_sales_receipts">Sales receipts via email</label>

            </div>

            <div class="acc_settings_right_option">

                <label class="switch">
                    <input id="send_purchase_receipts" type="checkbox" {{$send_purchase_receipts ? 'checked' : ''}}>
                    <span class="slider"></span>
                </label>

                <span class="slider_loading"></span>
                <label id="send_purchase_receipts_label" for="send_purchase_receipts">Purchase receipts via email</label>

            </div>

        </div>

    </div>

        {{--
    <div class="col-10 center-block module">

        <div class="module-header">
            <div class="module-title">Referrals</div>
        </div>

        <div class="module-content">

            <div class="acc_settings_referral_text">Earn cash by referring new users. <a href="/support/kb/10">Learn more.</a></div>

            <div class="last-input-wrap">

                <span class="hint--top" aria-label="Click to copy">

                    <label
                            class="support_input_label clipboard-btn"
                            for="referral_link"
                            data-clipboard-target="#referral_link"
                    >Referral Link</label>

                    <input id="referral_link" class="clipboard-btn" type="text" data-clipboard-target="#referral_link" value="https://Marketplace.io/r/{{Auth::id()}}" >

                </span>

            </div>

            <div class="acc_settings_referral_earnings">
                You have referred <b>{{$referrals_count}}</b> {{($referrals_count > 1 || $referrals_count == 0) ? 'users' : 'user'}} &mdash; and earned <b>{{$referrals_sum}}</b>.
            </div>

        </div>

    </div>

    --}}

    </div>

@stop
