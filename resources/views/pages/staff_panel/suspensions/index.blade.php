@extends('layouts.default')

@section('title')
    Suspensions &bullet; Staff Panel
@stop

@section('content')

    <div class="row">

    @include('sections.menu', ['page' => $page, 'menu_links' => $menu_links])

    <div class="grid6 float_right_override module">

        <div class="module-header">

            <div class="module-title">Suspensions
                @if($search)
                    - Search
                @endif
            </div>

            <div class="module_btns_wrap">

                @if ($search)
                    <span id="go_back_btn" class="module_btn"><i class="icon-left-arrow"></i> Back</span>
                @else
                    <a class="module_btn" href="{{ route('staff_panel.suspensions.new') }}">New</a>
                @endif

                <span class="dropdown">

                    <span class="module_btn dropdown-toggle module_btn_dropdown_btn_w_arrow" data-toggle="dropdown">Sort by {{$display_sort}}</span>

                    <ul class="module-dropdown-menu dropdown-menu dropdown-menu-right">
                        <li>
                            <a href="{{$sort_link}}">Oldest</a>
                        </li>
                        <li><a href="{{ route('staff_panel.suspensions') }}">Newest</a></li>
                    </ul>

                </span>

                <span class="dropdown">

                <span class="module_btn dropdown-toggle module_btn_dropdown_btn_w_search" data-toggle="dropdown" title="Search"></span>

                <ul class="module-dropdown-menu module-dropdown-menu-search dropdown-menu dropdown-menu-right">
                    <li>
                        <form action="{{ route('staff_panel.suspensions') }}" method="GET">
                            <input  type="text" name="query" value="{{$search_query}}" placeholder="Search" autofocus>
                            <input class="hidden" type="submit">
                        </form>
                    </li>
                </ul>

            </span>

            </div>

        </div>

        @if (!empty($suspensions_list))

            <div class="module-content no-padding overflow-x-scroll">

                <table class="table table-hover" style="min-width: 700px;">

                    <thead>
                    <tr>
                        <th>Steam ID</th>
                        <th>Reason</th>
                        <th>Staff</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                    </thead>

                    <tbody class="table-striped">

                        @foreach ($suspensions_list as $suspension)

                            <tr class="white-space-nowrap font-size-14">

                                <td>
                                    <a href="https://steamcommunity.com/profiles/{{$suspension['steam_id']}}" target="_blank">{{$suspension['steam_id']}}</a>
                                </td>

                                <td>{{$suspension['reason']}}</td>

                                <td>{{$suspension['staff_username']}}</td>

                                <td>{{$suspension['length']}}</td>

                                <td>
                                    <form action="{{route('staff_panel.suspensions.lift')}}" method="post">
                                        <input type="hidden" name="lift_suspension" value="{{$suspension['id']}}">
                                        {{csrf_field()}}
                                        <span class="form_submit_btn_w_alert btn btn-small" data-alert="Lift suspension for {{$suspension['steam_id']}}?">Lift</span>
                                    </form>
                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

            @if ($suspensions_data->links() != '')
                {{ $suspensions_data->appends(['sort' => Request::input('sort')])->links() }}
            @endif

        @else

            <div class="module-content">
                @if ($search)
                    No results for <b>{{$search_query}}</b>
                @else
                    Nothing to see here {{randomEmoji()}}
                @endif
            </div>

        @endif

    </div>

    </div>

@stop