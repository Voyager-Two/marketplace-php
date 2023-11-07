@extends('layouts.default')

@section('title')
    Cashout Requests &bullet; Staff Panel
@stop
@section('content')

    <div class="row">

    @include('sections.menu', ['page' => $page, 'menu_links' => $menu_links])

    <div class="grid6 module float_right_override">

        <div class="module-header">
            <div class="module-title">Cashout Requests</div>
        </div>

        @if (!empty($cashout_request))

            <div class="module-content">

                <a href="//steamcommunity.com/profiles/{{$cashout_request->steam_id}}" target="_blank">{{$cashout_request->username}}</a>
                &nbsp;&middot;&nbsp;

                {{parseTime($cashout_request->time, Auth::user()->getTimeZone(), 'dateTime')}}
                &nbsp;&middot;&nbsp;

                {{$country_list[$cashout_request->country]}}

                <div class="module_content_divider_1px"></div>

                {{$cashout_method_text}}
                &nbsp;&middot;&nbsp;

                {{priceOutput($cashout_request->amount)}}
                &nbsp;&middot;&nbsp;

                {{$cashout_request->send_address}}

                <div class="module_content_divider_1px"></div>

                {{priceOutput($total_in_sales)}} earned from sales<br>

                {{priceOutput($total_cashout)}} cashed out<br>

                {{priceOutput($total_cashout_method)}} cashed out ({{$cashout_method_text}})

                <div class="module_content_divider_1px"></div>

                <form method="post" action="{{route('staff_panel.cashout_requests.post')}}">

                    {{csrf_field()}}

                    <input type="hidden" name="cashout_request_id" value="{{$cashout_request->id}}">

                    <button type="submit" name="approve" value="1" class="btn btn-purple">Mark as Sent</button>&nbsp;&nbsp;
                    <button type="submit" name="decline" value="1" class="btn btn-red">Decline</button>

                </form>

            </div>

        @else
            <div class="module-content">
                Nothing to see here {{randomEmoji()}}
            </div>
        @endif

    </div>

    </div>

@stop