@extends('layouts.default')

@section('title', 'Feedback')

@section('content')

    <div class="row">

    <div class="col-6 center-block module">

        <div class="module-header">
            <div class="module-title">Send us feedback 📣</div>
        </div>

        <div class='module-content'>
            <form id="contact_email_form_wrap" action="{{ route('feedback') }}" method="POST">

                <div class="first-input-wrap">
                    <textarea id="contact_textarea" class="ready_input_focus" name="message" placeholder="How are we doing? What can we improve?">{{ Request::old('message') ?: '' }}</textarea>
                </div>

                <input class="not_in_my_house" type="text" name="not_in_my_house">

                {{ csrf_field() }}

                <input class="form-submit-btn right btn btn-purple" type="submit" value="Send">

            </form>
        </div>

    </div>

    </div>

@stop