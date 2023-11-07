<div class="grid-menu first">
    <ul class="menu-links">

        @foreach($menu_links as $menu_link)

            @if ($page == $menu_link['page'])
                @php
                    $active = "menu-links-active";
                    $active_arrow = "right-arrow-active";
                @endphp
            @else
                @php
                    $active = "";
                    $active_arrow = "right-arrow";
                @endphp
            @endif

            <li><a class="{{ $active }}" href="/{{ Request::segment(1) }}/{{$menu_link['page']}}">{{$menu_link['name']}}<div class="{{ $active_arrow }}"></div></a></li>

        @endforeach

    </ul>
</div>