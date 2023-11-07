@extends('layouts.default')

@section('title')
    IP Search &bullet; Staff Panel
@stop
@section('content')

    <div class="row">

    @include('sections.menu', ['page' => $page, 'menu_links' => $menu_links])

        @foreach ($errors->all() as $error)
            {{ alert($error) }}
        @endforeach

        <div class="grid6 module float_right_override">

            <div class="module-header">

                <div class="module-title">
                    Tools &nbsp;&middot;&nbsp; IP Search
                    @if (Request::input('ip_address_or_user_id'))
                        &nbsp;&middot;&nbsp; {{Request::input('ip_address_or_user_id')}}
                    @endif
                </div>

                <div class="module_btns_wrap">
                    <span id="go_back_btn" class="module_btn"><i class="icon-left-arrow"></i> Back</span>
                </div>

            </div>

            <div class="module-content">

                @if ($search_type == '')

                    Enter an IP to return all users who have accessed with given IP.<br>
                    Enter a User ID to return all IP addresses for a user.

                    <div class="module_content_divider_1px"></div>

                    <form method="post" action="{{route('staff_panel.tools.ip_search')}}">

                        {{csrf_field()}}

                        <input class="ready_input_focus" type="text" name="ip_address_or_user_id" placeholder="IP Address or User ID">
                        &nbsp;
                        <button type="submit" class="btn btn-purple">Search</button>

                    </form>

                @elseif ($search_results->count())

                    @if ($search_type == 'ip')

                        @foreach ($search_results as $result)

                            <i class="bullet_arrow icon-left-arrow"></i>
                            User ID: {{$result->user_id}} &nbsp;&middot;&nbsp;
                            Steam: <a href="//steamcommunity.com/profiles/{{$result->steam_id}}" target="_blank">{{$result->username}}</a> &nbsp;&middot;&nbsp;
                            {{parseTime($result->time, Auth::user()->getTimeZone(), 'dateTime')}}

                            @if (!$loop->last)
                                <div class="module_content_divider_1px"></div>
                            @endif

                        @endforeach

                    @elseif ($search_type == 'user_id')

                        @foreach ($search_results as $result)

                            <i class="bullet_arrow icon-left-arrow"></i>
                            {{inet_ntop($result->ip)}} &nbsp;&middot;&nbsp;
                            {{parseTime($result->time, Auth::user()->getTimeZone(), 'dateTime')}}

                            <br>

                        @endforeach

                    @endif

                @else

                    No match found

                @endif

            </div>

        </div>

    </div>

@stop