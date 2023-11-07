@extends('layouts.default')

@section('title', 'Cart')

@section('content')

    @if ($removed_cart_item_names->count())

        <div class="col-5 center-block module">

            <div class="module-header">

                <div class="module-title">Sold items removed from your cart:</div>

            </div>

            <div class='module-content text-align-center'>

                @foreach ($removed_cart_item_names as $removed_cart_item_name)

                    <a href="/sale/{{$removed_cart_item_name->id}}">
                    {{$removed_cart_item_name->name}}
                    {!! $removed_cart_item_name->exterior != 0 ? '<span title="'.getExteriorTitle($removed_cart_item_name->exterior).'">('.getExteriorTitleAbbr($removed_cart_item_name->exterior).')</span>' : '' !!}
                    </a>
                    <br>

                @endforeach

            </div>

        </div>
        <br>

    @endif

    @if ($cart_total_count > 0)

    <div class="col-4 center-block module">

        <div class="module-header">

            <div class="module-title">Cart (<span class="cart_item_count">{{$cart_total_count}}</span> {{$cart_total_count > 1 ? 'items' : 'item'}})</div>

            <div class="module_btns_wrap">

                <span class="next_form_submit_btn module_btn" title="Remove all items from cart">Clear</span>
                <form action="{{route('cart')}}" method="post">
                    <input type="hidden" name="clear_cart" value="1">
                    {{csrf_field()}}
                </form>

            </div>

        </div>

        <div class='module-content cart_total_module_content'>

            <span class="cart_total_text_wrap">Total &middot; <span class="cart_item_total">{{priceOutput($cart_total_cost)}}</span></span>

            <br>

            <span   class="cart_deliver_btn btn btn-purple hint--bottom hint--long"
                    data-item-count="{{$cart_total_count}}"
                    data-total-cost="{{$cart_total_cost}}"
                    aria-label="Items will be delivered to your Steam inventory.">
                Purchase & Deliver
            </span>

            <span class="hint--bottom hint--long" aria-label="Items will be stored in your {{config('app.name')}} inventory.">

                <button class="cart_store_btn display-block btn btn-purple" disabled
                      data-item-count="{{$cart_total_count}}"
                      data-total-cost="{{$cart_total_cost}}">
                    Purchase & Store
                </button>

            </span>

            <div class="cart_ajax_msg"></div>

        </div>

    </div>

    <br>

    @endif

    <div class="row row-condensed row-centered">
        @include('sections.items_for_sale', ['no_result_alert' => 'Nothing to see here '.randomEmoji()])
    </div>

@stop
