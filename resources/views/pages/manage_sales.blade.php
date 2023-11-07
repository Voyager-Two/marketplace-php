@extends('layouts.default')

@section('title', 'Manage Sales')

@section('content')

    <div class="row">

    <div class="col-12 center-block module">

        <div class="module-header">

            <div class="module-title">Manage Sales</div>

            <div class="module_btns_wrap">

                <span class="dropdown">

                    <span class="module_btn dropdown-toggle module_btn_dropdown_btn_w_arrow" data-toggle="dropdown">Game: {{$display_game}}</span>

                    <ul class="module-dropdown-menu dropdown-menu dropdown-menu-right">

                        @foreach ($games as $_app_id => $game_title)
                            <li><a href="{{route('manage_sales', ['game' => $_app_id, 'filter' => Request::input('filter'), 'sort' => Request::input('sort'), 'page' => Request::input('page')])}}">{{$game_title}}</a></li>
                        @endforeach

                        <li><a href="{{route('manage_sales', ['game' => 'all', 'filter' => Request::input('filter'), 'sort' => Request::input('sort'), 'page' => Request::input('page')])}}">All</a></li>

                    </ul>

                </span>

                <span class="dropdown">

                    <span class="module_btn dropdown-toggle module_btn_dropdown_btn_w_arrow" data-toggle="dropdown">Filter by {{$display_filter}}</span>

                    <ul class="module-dropdown-menu dropdown-menu dropdown-menu-right">

                        <li><a href="{{route('manage_sales', ['game' => Request::input('game'), 'filter' => 'sold', 'sort' => Request::input('sort'), 'page' => Request::input('page')])}}">Sold</a></li>

                        <li><a href="{{route('manage_sales', ['game' => Request::input('game'), 'filter' => 'on-sale', 'sort' => Request::input('sort'), 'page' => Request::input('page')])}}">On-Sale</a></li>

                        <li><a href="{{route('manage_sales', ['game' => Request::input('game'), 'filter' => 'cancelled', 'sort' => Request::input('sort'), 'page' => Request::input('page')])}}">Cancelled</a></li>

                        <li><a href="{{route('manage_sales', ['game' => Request::input('game'), 'filter' => 'all', 'sort' => Request::input('sort'), 'page' => Request::input('page')])}}">All</a></li>

                    </ul>

                </span>

                <span class="dropdown">

                    <span class="module_btn dropdown-toggle module_btn_dropdown_btn_w_arrow" data-toggle="dropdown">Sort by {{$display_sort}}</span>

                    <ul class="module-dropdown-menu dropdown-menu dropdown-menu-right">

                        <li><a href="{{route('manage_sales', ['game' => Request::input('game'), 'filter' => Request::input('filter'), 'sort' => 'o', 'page' => Request::input('page')])}}">Oldest</a></li>

                        <li><a href="{{route('manage_sales', ['game' => Request::input('game'), 'filter' => Request::input('filter'), 'sort' => 'n', 'page' => Request::input('page')])}}">Newest</a></li>

                    </ul>

                </span>

            </div>

        </div>

        @if (count($user_sales) > 0)

        <div class="module-content no-padding overflow-x-scroll">

            <table id="manage_sales_table_wrap" class="table table-hover">

                <thead>
                <tr>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Price</th>
                    <th>Options</th>
                    <th title="Date Listed">Date</th>
                    <th title="Sale ID">ID</th>
                </tr>
                </thead>

                <tbody class="table-striped">

                    @foreach ($user_sales as $sale)

                        <tr class="white-space-nowrap">
                            <td><a href="/sale/{{$sale['id']}}">{{$sale['market_name']}}</a></td>

                            @if ($sale['status'] == config('app.sale_sold'))

                                <td>Sold</td>

                            @elseif ($sale['status'] == config('app.sale_cancelled'))

                                @if ($sale['trade_status'] == 1)

                                    <td><span class="hint--left" aria-label="Sale cancelled, item returned"><span class="red_circle"></span></span></td>

                                @elseif ($sale['trade_status'] != 1)

                                    <td class="manage_sales_status">
                                        <span class="hint--left hint--long" aria-label="Sale cancelled, item not returned!">
                                            <span class="red_circle"></span> [<span class="cancelled_item_send_offer link" data-sale-id="{{$sale['id']}}">send offer</span>]
                                        </span>
                                    </td>

                                @endif

                            @else

                                <td class="manage_sales_status">
                                    <span class="green_circle hint--top" aria-label="On Sale"></span>
                                    <span class="cancel_sale_btn manage_sales_cancel_sale_btn hint--top" aria-label="Cancel" data-sale-id="{{$sale['id']}}"><i class="icon-x"></i></span>
                                </td>

                            @endif

                            <td class="pos-relative">

                                @if ($sale['status'] == config('app.sale_active'))
                                    $<input
                                            class="manage_sales_price_input"
                                            name="price" type="number" step="0.01" min="0.02" max="9999.99"
                                            value="{{$sale['price']}}" data-sale-id="{{$sale['id']}}"
                                    >
                                    <span class="manage_sales_saved_msg">Saved</span>
                                @else
                                    {{priceOutput($sale['price'])}}
                                @endif

                            </td>

                            <td>

                                <span class="hint--top" aria-label="Search for similar items">
                                    <a
                                        class="btn btn-purple btn-transparent-purple btn-small"
                                        href="/search?name={{$sale['name']}}@if ($sale['exterior_title'])&exterior={{$sale['exterior']}}@endif"
                                    >
                                        <i class="small_btn_icon icon-search"></i>
                                    </a>
                                </span>

                                @if ($sale['status'] == config('app.sale_active') && !$sale['boost'])
                                    <span
                                        class="manage_sales_boost_btn hint--top"
                                        data-sale-id="{{$sale['id']}}"
                                        data-market-name="{{$sale['market_name']}}"
                                        aria-label="Promote for {{priceOutput(config('app.boost_price'))}}"
                                    >
                                        <span class="btn btn-purple btn-transparent-purple btn-small">
                                            <i class="small_btn_icon icon-rocket"></i>
                                        </span>
                                    </span>
                                @endif

                                @if ($sale['status'] == config('app.sale_active') && $sale['boost'])
                                    <span class="font-size-13 purple3">Promoted</span>
                                @endif

                            </td>

                            <td>{{$sale['time_display']}}</td>

                            <td>{{$sale['id']}}</td>
                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

        @if ($db_sales->links() != '')

            {{ $db_sales->appends($_GET)->links() }}

        @endif

        @else

            <div class="module-content">
                Nothing to see here {{randomEmoji()}}
            </div>

        @endif

    </div>

    </div>

@stop