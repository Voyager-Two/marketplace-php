@extends('layouts.default')

@section('title')
    Tools &bullet; Staff Panel
@stop
@section('content')

    <div class="row">

    @include('sections.menu', ['page' => $page, 'menu_links' => $menu_links])

        <div class="grid6 module float_right_override">

            <div class="module-header">
                <div class="module-title">Tools</div>
            </div>

            <div class="module-content">

                <i class="bullet_arrow icon-left-arrow"></i>
                <a href="{{route('staff_panel.tools.ip_search')}}">IP search</a><br>

                <i class="bullet_arrow icon-left-arrow"></i>
                <a href="{{route('staff_panel.tools.give_credit')}}">Give credit</a><br>

                <i class="bullet_arrow icon-left-arrow"></i>
                <a href="{{route('staff_panel.tools.login')}}">Login to account</a>

            </div>

        </div>

    </div>

@stop