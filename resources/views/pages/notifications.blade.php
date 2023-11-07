@extends('layouts.default')

@section('title', 'Notifications')

@section('content')

    <div class="row">

        <div class="col-8 center-block module">

            <div class="module-header">

                <div class="module-title">
                    @if(Request::input('all'))
                        All
                    @endif
                    Notifications
                </div>

                <div class="module_btns_wrap">

                    <span id="go_back_btn" class="module_btn"><i class="icon-left-arrow"></i> Back</span>

                    <a href="{{route('notifications')}}?all=1" class="module_btn">View All</a>

                </div>

            </div>

            <div class='module-content'>

                @if ($notifications->count())

                    @foreach ($notifications as $notification)

                        <span class="bullet_arrow"><i class="icon-left-arrow"></i></span>
                        {{parseNotification($notification)}}

                        @if (!$loop->last)
                            <div class="module_content_divider_1px"></div>
                        @endif

                    @endforeach

                @else

                    <div class="text-align-center">No new notifications {{randomEmoji()}}</div>

                @endif

            </div>

            @if ($paginate)

                @if ($notifications->links() != '')
                    {{ $notifications->appends('all', 1)->links() }}
                @endif

            @endif

        </div>

    </div>

@stop