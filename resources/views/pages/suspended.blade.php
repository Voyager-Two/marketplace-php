@extends('layouts.default')

@section('title', 'Suspended')

@section('content')

    <div class="row">

    <div class="col-7 center-block module">

        <div class="module-header">
            <div class="module-title-error">Account suspended</div>
        </div>

        <div class="module-content">

            @foreach ($user_suspensions_list_array as $user_suspensions_list)

                <b>Reason:</b> {{$user_suspensions_list['reason']}}
                <br>
                <b>Expires:</b> {{$user_suspensions_list['expires']}}

                @if ($loop->count > 1 && !$loop->last)
                    <div class="module_content_divider_1px"></div>
                @endif

            @endforeach

            <div class="module_content_divider"></div>

            <span class="font-size-13">
                Dispute this suspension or cashout any remaining funds by <a href="/support">contacting us</a>.</a><br>
                Expiration may be delayed up to 15 minutes.
            </span>

        </div>

    </div>

    </div>

@stop