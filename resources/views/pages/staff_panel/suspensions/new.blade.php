@extends('layouts.default')

@section('title')
    Suspensions &bullet; Staff Panel
@stop

@section('content')

    <div class="row">

    @include('sections.menu', ['page' => $page, 'menu_links' => $menu_links])

    <div class="grid6 module float_right_override">

        <div class="module-header">
            <div class="module-title">New Suspension</div>
            <div class="module_btns_wrap">
                <span id="go_back_btn" class="module_btn"><i class="icon-left-arrow"></i> Back</span>
            </div>
        </div>

        <div class="module-content">

            <form action="{{route('staff_panel.suspensions.new')}}" method="post">

                <div class="first-input-wrap">
                    <input class="ready_input_focus" type="text" name="id" placeholder="User ID or Steam ID">
                </div>

                <div class="last-input-wrap">

                    <select name="reason_id">
                        {!! $suspension_reason_options !!}
                    </select>

                    <br><br>

                    <input class="btn btn-purple" type="submit" value="Suspend">

                </div>

                {{csrf_field()}}

            </form>

        </div>

    </div>

    </div>

@stop