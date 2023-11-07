@extends('layouts.default')

@section('title', 'Search')

@section('content')

    @include('sections.items_easy_nav')

    @include('sections.items_search')

    <div class="jscroll">

        <div class="row row-condensed row-centered">
            @include('sections.items_for_sale', ['no_result_alert' => 'No results.'])
        </div>

        <div class="item_display_pagination_wrap hidden">
            {{ isset($itemsForSale) ? $itemsForSale->appends($_GET)->links() : '' }}
        </div>

    </div>

@stop