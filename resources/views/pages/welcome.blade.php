@extends('layouts.default')

@section('title', 'Welcome')

@section('content')

    @foreach ($errors->all() as $error)
        {{ alert($error) }}
    @endforeach

    <div class="row">

    <div class="grid3 center3 module">

        <div class="module-header">
            <div class="module-title">Welcome! Let's get started...</div>
        </div>

        <div class="module-content">

            Email:

            <form id="get_started_form" action="{{ route('welcome') }}" method="POST">

                <div class="first-input-wrap">
                    <input class="ready_input_focus" type="text" name="contact_email" value="{{ Request::old('contact_email') ?: '' }}" placeholder="Email" tabindex="1">
                </div>

                {{ csrf_field() }}

                <div class="get_started_wrap_terms">By continuing, you agree to our <a href="/terms" target="_blank" tabindex="4">Terms of Service</a>.</div>

                <div class="module_content_divider"></div>

            </form>

        </div>

    </div>

    </div>

@stop
