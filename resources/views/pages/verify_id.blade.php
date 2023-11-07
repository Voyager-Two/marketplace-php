@extends('layouts.default')

@section('title', 'Identity Verification')

@section('content')

    <div class="row">

        @foreach ($errors->all() as $error)
            {{ alert($error) }}
        @endforeach

        <div class="col-8 module center-block">

            <div class="module-header">

                <div class="module-title">Identity Verification</div>

            </div>

            <div class="module-content">

            @if ($id_verified == 0)

                <form id="verify_id_form" method="post" enctype="multipart/form-data" action="{{route('verify_id')}}">

                    {{csrf_field()}}

                    <div class="verify_id_bullet">
                        &bullet; All pictures you send to us are <u>deleted</u> after verification.
                    </div>

                    <div class="verify_id_bullet">
                        &bullet; Take the pictures with <u>your phone's camera</u> and make sure it is <u>clear</u>
                    </div>

                    <div class="verify_id_bullet">
                        &bullet; The only information we keep is your full name, date of birth, and country.
                    </div>

                    <div class="module_content_divider"></div>

                    <div class="verify_input_wrap">
                        <label class="support_input_label" for="full_name">Full Name</label>
                        <input id="full_name" name="full_name" type="text" placeholder="Full Name" value="{{Request::old('full_name') ?: ''}}">
                    </div>

                    <div class="verify_input_wrap">
                        <label class="support_input_label" for="date_of_birth">Date of Birth</label>
                        <span class="hint--top" aria-label="MM/DD/YYYY">
                            <input id="date_of_birth" name="date_of_birth" type="text" placeholder="01/20/1945" value="{{Request::old('date_of_birth') ?: ''}}">
                        </span>
                    </div>

                    <div class="verify_input_wrap">
                        <label class="support_input_label" for="change_country">Country</label>
                        <select id="change_country" name="country">
                            {!! $countries_options !!}
                        </select>
                    </div>

                    <div class="module_content_divider verify_input_wrap"></div>

                    <div class="verify_id_bullet verify_pic_wrap">

                        Upload a picture of a government issued photo ID (Driver's License, State ID, Passport)<br>
                        Write "{{config('app.legal_name')}}" on a small piece of paper and place it next to your ID.<br>

                        <label class="btn btn-green visible_file_input">
                            Select Picture <input class="select_file_input invisible_file_input verify_select_input" type="file" name="photo_id" style="display:none">
                        </label>

                        <span class="select_file_name"></span>

                    </div>

                    <div class="module_content_divider"></div>

                    <div class="verify_id_bullet verify_pic_wrap">

                        Upload a picture of a document showing name and address (Utility Bill, Bank, School)<br>

                        <label class="btn btn-green visible_file_input">
                            Select Picture <input class="select_file_input invisible_file_input verify_select_input" type="file" name="photo_document" style="display:none">
                        </label>

                        <span class="select_file_name"></span>

                    </div>

                    <div class="module_content_divider"></div>

                    <button name="submit_btn" class="verify_submit_btn btn btn-purple disable_btn_and_submit" type="submit">Submit Application</button>

                </form>

            @else

                We are reviewing your application. It should take 1 to 3 days.<br>Thank you for your patience.

            @endif

            </div>

        </div>

    </div>
@stop
