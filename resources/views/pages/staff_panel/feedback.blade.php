@extends('layouts.default')

@section('title')
    Feedback &bullet; Staff Panel
@stop
@section('content')

    <div class="row">

    @include('sections.menu', ['page' => $page, 'menu_links' => $menu_links])

    <div class="grid6 module float_right_override">

        <div class="module-header">
            <div class="module-title">User Feedback</div>
        </div>

        @if (count($feedback) > 0)

            <div class="module-content">

                @foreach ($feedback as $_feedback)

                    {{parseTime($_feedback->time, Auth::user()->getTimeZone(), 'date')}} &nbsp;&mdash;&nbsp; {{$_feedback->message}}

                    @if(!$loop->last)
                        <div class="module_content_divider_1px"></div>
                    @endif

                @endforeach

            </div>

        @else
            <div class="module-content">
                Nothing to see here {{randomEmoji()}}
            </div>
        @endif

        @if ($feedback->links() != '')
            {{ $feedback->links() }}
        @endif

    </div>

    </div>

@stop