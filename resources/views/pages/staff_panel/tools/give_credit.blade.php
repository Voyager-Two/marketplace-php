@extends('layouts.default')

@section('title')
    Give Credit &bullet; Staff Panel
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
                    Tools &nbsp;&middot;&nbsp; Give Credit
                </div>

                <div class="module_btns_wrap">
                    <span id="go_back_btn" class="module_btn"><i class="icon-left-arrow"></i> Back</span>
                </div>

            </div>

            <div class="module-content">

                <form method="post" action="{{route('staff_panel.tools.give_credit')}}">

                    {{csrf_field()}}

                    <input class="ready_input_focus" type="text" name="credit_user_id_or_steam_id" placeholder="User ID or Steam ID">
                    <br>
                    <input type="text" name="credit_amount" placeholder="Amount">
                    &nbsp;
                    <button type="submit" class="btn btn-purple">Send</button>

                </form>

            </div>

        </div>

    </div>

@stop