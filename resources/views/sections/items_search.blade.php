<div id="items_search_wrap">

    <form class="do_not_submit_empty_fields" action="{{route('search')}}" method="GET">

        <input id="items_search" type="text" name="name" placeholder="Search" value="{{ Request::input('name') ?: '' }}">

        <button class="items_search_submit_btn btn btn-purple" type="submit">
            <i class="icon-search"></i>
        </button>

        <br>

        <div class="display-inline-block">

            <div id="items_search_options"

                @if (Route::currentRouteName() == 'search')
                    style="display:block"
                @endif
            >

                <label for="select_sort_by">Sort by</label>
                <select id="select_sort_by" name="sort" class="select_small">
                    @foreach ($sort_options as $value => $name)
                        <option value="{{$value}}" {{Request::input('sort') == $value ? 'selected' : ''}}>{{$name}}</option>
                    @endforeach
                </select>

                @if ($app_id == config('app.csgo'))

                    <label for="select_type">Type</label>
                    <select id="select_type" name="type" class="select_small">
                        @foreach ($type_options as $value => $name)
                            <option value="{{$value}}" {{Request::input('type') == $value ? 'selected' : ''}}>{{$name}}</option>
                        @endforeach
                    </select>

                    <label for="select_exterior">Exterior</label>
                    <select id="select_exterior" name="exterior" class="select_small">
                        @foreach ($exterior_options as $value => $name)
                            <option value="{{$value}}" {{Request::input('exterior') == $value ? 'selected' : ''}}>{{$name}}</option>
                        @endforeach
                    </select>

                    <label for="select_grade">Grade</label>
                    <select id="select_grade" name="grade" class="select_small">
                        @foreach ($grade_options as $value => $name)
                            <option value="{{$value}}" {{Request::input('grade') == $value ? 'selected' : ''}}>{{$name}}</option>
                        @endforeach
                    </select>

                    <label for="select_stickers">Stickers</label>
                    <select id="select_stickers" name="stickers" class="select_small">
                        @foreach ($stickers_options as $value => $name)
                            <option value="{{$value}}" {{Request::input('stickers') == $value ? 'selected' : ''}}>{{$name}}</option>
                        @endforeach
                    </select>

                    <label for="select_stattrack">StatTrack</label>
                    <select id="select_stattrack" name="stattrack" class="select_small">
                        @foreach ($stattrack_options as $value => $name)
                            <option value="{{$value}}" {{Request::input('stattrack') == $value ? 'selected' : ''}}>{{$name}}</option>
                        @endforeach
                    </select>

                @elseif ($app_id == config('app.h1z1_kotk'))

                    <div class="items_search_h1z1_kotk_options">

                    <label for="select_slot">Slot</label>
                    <select id="select_slot" name="slot" class="select_small">
                        @foreach ($h1z1_kotk_slot_options as $value => $name)
                            <option value="{{$value}}" {{Request::input('slot') == $value ? 'selected' : ''}}>{{$name}}</option>
                        @endforeach
                    </select>

                    <label for="select_category">Category</label>
                    <select id="select_category" name="category" class="select_small">
                        @foreach ($h1z1_kotk_category_options as $value => $name)
                            <option value="{{$value}}" {{Request::input('category') == $value ? 'selected' : ''}}>{{$name}}</option>
                        @endforeach
                    </select>

                    </div>

                @elseif ($app_id == config('app.dota2'))

                    <label for="select_hero">Hero</label>
                    <select id="select_hero" name="hero" class="select_small">
                        @foreach ($dota2_heroes_options as $value => $name)
                            <option value="{{$value}}" {{Request::input('hero') == $value ? 'selected' : ''}}>{{$name}}</option>
                        @endforeach
                    </select>

                @elseif ($app_id == config('app.pubg'))

                    <br>

                @endif

                <div class="items_search_price_wrap display-inline-block">
                    <label for="min_price">Min Price</label>
                    <input id="min_price" name="min_price" type="number" step="0.01" min="{{config('app.min_item_price')}}" max="{{config('app.max_item_price')}}" value="{{ Request::input('min_price') ?: '' }}">
                </div>

                <div class="items_search_price_wrap display-inline-block">
                    <label for="max_price">Max Price</label>
                    <input id="max_price" name="max_price" type="number" step="0.01" min="{{config('app.min_item_price')}}" max="{{config('app.max_item_price')}}" value="{{ Request::input('max_price') ?: '' }}">
                </div>

                <!--
                <div id="items_search_options_checkbox_wrap" class="display-inline-block">
                    <label for="lowest_wear">
                        <input id="lowest_wear" type="checkbox" value="0">
                        Lowest wear
                    </label>
                </div>
                -->

            </div>

        </div>

    </form>

</div>