<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class Cart extends Model
{
    protected $table = 'cart_items';

    public $timestamps = false;

    public function add($sale_id)
    {
        $this->user_id = Auth::id();
        $this->sale_id = $sale_id;
        $this->time = time();

        $this->save();

        Cache::forget('cart_count:uid:'.Auth::id());
        Cache::forget('cart_cost:uid:'.Auth::id());
    }

    public function remove($sale_id)
    {
        $this->where(['user_id' => Auth::id(), 'sale_id' => $sale_id])->delete();

        Cache::forget('cart_count:uid:'.Auth::id());
        Cache::forget('cart_cost:uid:'.Auth::id());
    }

    public function getSaleIds($strict=0)
    {
        // this excludes items that have been already sold
        if ($strict) {

            $all_cart_items =
                $this->where('cart_items.user_id', Auth::id())
                    ->join('item_sales', 'cart_items.sale_id', '=', 'item_sales.id')
                    ->where('item_sales.status', '=', config('app.sale_active'))
                    ->select('cart_items.sale_id')
                    ->get();

        } else {

            $all_cart_items = $this->where('user_id', Auth::id())->select('sale_id')->get();
        }

        $cart_sale_ids = [];

        foreach ($all_cart_items as $cart_item) {
            array_push($cart_sale_ids, $cart_item->sale_id);
        }

        return $cart_sale_ids;
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

}