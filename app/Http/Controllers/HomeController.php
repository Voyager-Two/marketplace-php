<?php

namespace App\Http\Controllers;
use App\Models\Cart;
use App\Models\ItemSales;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('throttle:30,1');
    }

    public function home(Request $request, SalesController $sales, SearchController $_search, Cart $cartItems, $user_id=0)
    {
        /* Home sweet home */

        // handle referrals
        if (!Auth::check() && $user_id != 0) {

            $user_id = DB::table('users')->where('id', $user_id)->value('id');

            if ($user_id != null) {
                session(['referral_user_id' => $user_id]);
            }
        }

        $connection_ip = $request->ip();

        // keep track of all ip addresses the user has connected with
        if (Auth::check() && !empty($connection_ip)) {

            $user_id = Auth::id();
            $user_latest_ip = Auth::user()->getLatestIp();

            // we store IP addresses in our database in binary form
            // which allows us to support both IPv4 and IPv6
            $connection_ip_binary = inet_pton($connection_ip);

            if ($user_latest_ip != $connection_ip_binary) {

                // update user latest ip
                Auth::user()->latest_ip = $connection_ip_binary;
                Auth::user()->save();

                // their IP has changed
                // insert the new IP if it not already in the database
                $already_exists =
                    DB::table('users_ip_history')
                    ->where(['user_id' => $user_id, 'ip' => $connection_ip_binary])
                    ->value('id');

                if ($already_exists == null) {

                    // not in database, insert it
                    DB::table('users_ip_history')
                        ->insert(
                            [
                                'user_id' => $user_id,
                                'ip' => $connection_ip_binary,
                                'time' => time()
                            ]
                        );
                }
            }
        }

        $app_id = getAppId();
        
        $page = $request->has('page') ? $request->query('page') : 1;
        $page = (!is_numeric($page)) ? 1 : $page;

        $cache_minutes = 3;

        $itemsForSale = Cache::remember('home_items:appid:'.$app_id.':page:'.$page, $cache_minutes, function () use($app_id) {
            return ItemSales::where(['appid' => $app_id, 'status' => config('app.sale_active'), 'private' => 0])
                            ->orderBy('boost', 'desc')
                            ->orderBy('price', 'desc')
                            ->simplePaginate(20);
        });

        $itemsForSaleDisplay = $sales->parseItemsForSale($itemsForSale, $page, $cache_minutes);

        $cart_sale_ids = Auth::check() ? $cartItems->getSaleIds() : [];

        $return_array =
            [
                'itemsForSaleDisplay' => $itemsForSaleDisplay,
                'itemsForSale' => $itemsForSale,
                'sort_options' => $_search->getSort(),
                'cart_sale_ids' => $cart_sale_ids
            ];

        if ($app_id == config('app.csgo')) {

            $return_array = array_merge($return_array, [
                'item_easy_nav' => $sales->getItemsEasyNav(),
                'exterior_options' => $_search->getExterior(),
                'type_options' => $_search->getType(),
                'grade_options' => $_search->getGrade(),
                'stickers_options' => $_search->getStickers(),
                'stattrack_options' => $_search->getStatTrack()
            ]);

        } elseif ($app_id == config('app.h1z1_kotk')) {

            $return_array = array_merge($return_array, [
                'h1z1_kotk_slot_options' => $_search->getH1Z1KotKSlot(),
                'h1z1_kotk_category_options' => $_search->getH1Z1KotKCategory()
            ]);

        } elseif ($app_id == config('app.dota2')) {

            $return_array = array_merge($return_array, [
                'dota2_heroes_options' => $_search->getDota2Heroes()
            ]);
        }

        return view('pages.home', $return_array);
    }

    public function disableFrontPageText()
    {
        session(['disable_front_page_text' => 1]);
    }

    public function giveaway()
    {
        return view('pages.giveaway');
    }
}