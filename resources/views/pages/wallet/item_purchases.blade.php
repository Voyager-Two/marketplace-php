@extends('layouts.default')

@section('title')
    Item Purchases
@stop

@section('content')

    <div class="row">

    @include('sections.menu', ['page' => $page, 'menu_links' => $menu_links])

    <div class="grid6 module float_right_override">

        <div class="module-header">

            <div class="module-title">Item Purchases</div>

            <div class="module_btns_wrap">

                <span class="hint--left" aria-label="Resend all undelivered items">
                    <span class="module_btn next_form_submit_btn">Resend All</span>
                    <form class="hidden" method="post" action="{{route('wallet.item_purchases.resend_all')}}">
                        {{csrf_field()}}
                    </form>
                </span>

            </div>

        </div>

        @if (count($user_purchases) > 0)

        <div class="module-content no-padding overflow-x-scroll">

            <table class="table table-hover" style="min-width: 700px;">

                <thead>
                <tr>
                    <th>Item</th>
                    <th>Price</th>
                    <th>Delivery Status</th>
                    <th>Date</th>
                </tr>
                </thead>

                <tbody class="table-striped">

                @foreach ($user_purchases as $sale_id => $purchase)

                    <tr class="white-space-nowrap">

                        <td>

                            <a href="/sale/{{$sale_id}}">
                                {{$purchase['name']}}
                                {!! $purchase['exterior'] != 0 ? '<span title="'.getExteriorTitle($purchase['exterior']).'">
                                ('.getExteriorTitleAbbr($purchase['exterior']).')</span>' : '' !!}
                            </a>

                        </td>

                        <td>
                            {{$purchase['price_output']}}
                        </td>

                        <td>

                            @if ($purchase['delivery_status'] == 1)

                                <span class="hint--left purple1" aria-label="Trade offer was sent and accepted">Delivered</span>

                            @elseif ($purchase['trade_id'] != 0)

                                <span class="hint--left" aria-label="Trade offer was sent">Sent</span>

                            @else

                                <span class="hint--left hint--long" aria-label="Click 'Resend All' to resend all undelivered items">Failed</span>
                            @endif

                        </td>

                        <td>{{$purchase['time_display']}}</td>

                    </tr>

                @endforeach

                </tbody>

            </table>

        </div>

        @if ($db_purchases->links() != '')

            {{ $db_purchases->appends(['sort' => Request::input('sort')])->links() }}

        @endif

        @else

            <div class="module-content">
                Nothing to see here {{randomEmoji()}}
            </div>

        @endif

    </div>

    </div>

@stop