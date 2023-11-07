@extends('layouts.default')

@section('title')
    ID Verification &bullet; Staff Panel
@stop
@section('content')

    <div class="row">

    @include('sections.menu', ['page' => $page, 'menu_links' => $menu_links])

    <div class="grid6 module float_right_override">

        <div class="module-header">
            <div class="module-title">ID Verification {{ empty($verify_id) == false ? '&middot; User ID: ' . $verify_id->user_id : '' }}</div>
        </div>

        <div class="module-content">

            @if (empty($verify_id))

                Nothing to see here {{randomEmoji()}}

            @else

                <form method="post" action="{{route('staff_panel.verify_id.post')}}">

                    {{csrf_field()}}

                    <input type="hidden" name="id_verification_id" value="{{$verify_id->id}}">

                    <button class="btn btn-purple" name="approve" value="1" type="submit">Approve</button> &nbsp;
                    <button class="btn btn-red" name="bad_picture" value="1" type="submit">Deny (Bad Picture)</button> &nbsp;
                    <button class="btn btn-red" name="deny_other" value="1" type="submit">Deny (Other)</button>

                    <div class="module_content_divider"></div>

                    <a href="//steamcommunity.com/profiles/{{$verify_id->steam_id}}" target="_blank">View Steam Profile</a><br>
                    Must have activity (games, friends) and be at least 2 months old.

                    <div class="module_content_divider"></div>

                    <div class="verify_input_wrap">
                        <label class="support_input_label" for="full_name">Name</label>
                        <input id="full_name" name="full_name" type="text" value="{{$verify_id->full_name}}">
                    </div>

                    <div class="verify_input_wrap">
                        <label class="support_input_label" for="date_of_birth">Date of Birth</label>
                        <span class="hint--top" aria-label="MM/DD/YYYY">
                            <input id="date_of_birth" name="date_of_birth" type="text" value="{{$verify_id->date_of_birth}}">
                        </span>
                    </div>

                    <div class="verify_input_wrap">
                        <label class="support_input_label" for="change_country">Country</label>
                        <select id="change_country" name="country">
                            {!! $countries_options !!}
                        </select>
                    </div>

                    <div class="verify_input_note">
                        @if (Auth::user()->getCountry() == $verify_id->country)
                            <i class="icon-checkmark vertical-align-middle"></i> Country matches IP address.
                        @else
                            <span class="red">
                                <i class="icon-x vertical-align-middle"></i> WARNING: Country does not match IP address.
                            </span>
                        @endif
                    </div>

                </form>

                <div class="module_content_divider"></div>

                <a href="{{$verify_img}}" target="_blank">
                    <img src="{{$verify_img}}" width="100%" />
                </a>

                <div class="module_content_divider"></div>

                <span class="font-size-14">Image meta data:</span><br>

                @foreach (json_decode($verify_id->img_data, 1) as $_key => $_data)

                    {{$_key}}: {{$_data}}

                    @if (!$loop->last)
                        &nbsp;&middot;&nbsp;
                    @endif

                @endforeach

                <div class="module_content_divider"></div>

                <a href="{{$verify_img2}}" target="_blank">
                    <img src="{{$verify_img2}}" width="100%" />
                </a>

                <div class="module_content_divider"></div>

                <span class="font-size-14">Image meta data:</span><br>

                @foreach (json_decode($verify_id->img2_data, 1) as $_key => $_data)

                    {{$_key}}: {{$_data}}

                    @if (!$loop->last)
                        &nbsp;&middot;&nbsp;
                    @endif

                @endforeach

            @endif

        </div>

    </div>

    </div>

@stop