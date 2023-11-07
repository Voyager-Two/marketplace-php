@if ($app_id == config('app.csgo'))

    <div id="items_easy_nav_dropdowns">

        @foreach ($item_easy_nav as $item_type => $items)

            <span class="dropdown">

                    <span class="dropdown-toggle btn btn-black" data-toggle="dropdown" href="#">{{$item_type}} <i class="icon-down-arrow btn_icon"></i></span>

                    <ul class="standard-dropdown-menu dropdown-menu dropdown-menu-left prevent-parent-scroll">

                        @foreach ($items as $item)
                            <li><a href="/search?name={{urlencode($item)}}">{{$item}}</a></li>
                        @endforeach

                    </ul>

                </span>

        @endforeach

    </div>

@endif