@extends('layouts.default')

@section('title')
    Logs &bullet; Staff Panel
@stop
@section('content')

    <div class="row">

    @include('sections.menu', ['page' => $page, 'menu_links' => $menu_links])

        <div class="grid6 module float_right_override">

            <div class="module-header">
                <div class="module-title">Action Logs</div>
            </div>

            <div class="module-content">

                @if (count($logs) > 0)

                    @foreach ($logs as $log)

                        <i class="bullet_arrow icon-left-arrow"></i>
                        {{$log->username}} &nbsp;&middot;&nbsp;

                        {{getStaffAction($log->type)}} &nbsp;&middot;&nbsp;

                        {{parseTime($log->time, Auth::user()->getTimeZone(), 'dateTime')}}

                        @if (!$loop->last)
                            <div class="module_content_divider_1px"></div>
                        @endif

                    @endforeach

                @else
                    Nothing to see here {{randomEmoji()}}
                @endif

            </div>

            @if ($logs->links() != '')
                {{ $logs->links() }}
            @endif

        </div>

    </div>

@stop