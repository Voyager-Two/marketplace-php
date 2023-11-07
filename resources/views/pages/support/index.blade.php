@extends('layouts.default')

@section('title', 'Support')

@section('content')

    <div class="row">

    <div class="grid3 center module">
        <div class="support_page_kb_wrap module-content module-content-rounded">

            <a href="/support/kb">
                <i class="support_page_kb_icon icon-question-mark"></i>
                <span class="support_page_kb_title">Knowledge Base</span>
                <i class="support_page_kb_icon icon-question-mark"></i>
            </a>

        </div>
    </div>

    @foreach ($errors->all() as $error)
        {{ alert($error) }}
    @endforeach

    <div class="col-6 center-block module">

        <div class="module-header">
            <div class="module-title">Contact Us</div>
        </div>

        <div class='module-content'>

            Our email is <a href="mailto:{{config('app.support_email')}}" target="_blank">{{config('app.support_email')}}</a><br>
            You can use the form below to email us:
            <br>

            <div class="module_content_divider"></div>

            <form id="contact_email_form_wrap" action="{{route('support.contact_form')}}" method="POST">

                <div class="first-input-wrap">
                    <label class="support_input_label" for="support_steam_id">Steam ID</label>
                    <input id="support_steam_id" type="text" name="steam_id" value="{{$steam_id}}" placeholder="Steam ID (optional)">
                </div>

                <div class="input-wrap">
                    <label class="support_input_label" for="support_email">Email</label>
                    <input id="support_email" type="text" name="contact_email" value="{{$email}}" placeholder="Email">
                </div>

                <div class="input-wrap">
                    <textarea id="contact_textarea" name="message" placeholder="How can we help?">{{ Request::old('message') ?: '' }}</textarea>
                </div>

                <input class="not_in_my_house" type="text" name="not_in_my_house">

                {{ csrf_field() }}

                <input class="form-submit-btn right btn btn-purple" type="submit" value="Send">

            </form>

        </div>

    </div>

    </div>

@stop