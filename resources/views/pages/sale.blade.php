@extends('layouts.default')

@section('title', !empty($itemsForSaleDisplay) ? $itemsForSaleDisplay[0]['market_name'] : 'Oops')

@section('content')

    <div class="row">

        @include('sections.items_for_sale', ['no_result_alert' => 'Sale not found.'])

        @if (!empty($itemsForSaleDisplay))

        <div class="col-10 module">

            <div class="module-header">

                <div class="module-title">Sale History</div>

                <div class="module_btns_wrap">

                </div>

            </div>

            <div class="module-content">

                <i>No stats available</i>

                <div class="module_content_divider_1px"></div>

                <a href="https://steamcommunity.com/market/listings/730/{{rawurlencode($itemsForSaleDisplay[0]['market_name'])}}" target="_blank">
                    View on Steam Market
                </a>

                &nbsp;&mdash;&nbsp;

                <a href="https://opskins.com/?app={{$app_id}}_{{getAppContextId($app_id)}}&loc=shop_search&search_item={{rawurlencode($itemsForSaleDisplay[0]['market_name'])}}&sort=lh" target="_blank">
                    Search on OPSkins
                </a>

            </div>

        </div>

        @endif

    </div>

@stop
