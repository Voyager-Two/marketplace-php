@if (Session::has('alert'))
    <div class="alert-wrap">
        <div class="alert shake">
            {!! Session::get('alert') !!}
            {{Request::session()->forget('alert')}}
        </div>
    </div>
@endif