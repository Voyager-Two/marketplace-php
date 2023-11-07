<?php

namespace App\Http\Controllers;
use App\Models\ItemSales;
use App\Models\Transactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\SteamInventoryController;

class SellController extends Controller
{
    protected $app_id = 0;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('throttle:15,1')->only('sellItems');
    }

    public function index ()
    {
        return view('pages.sell.index');
    }

    public function sellItems(Request $request, ItemSales $itemSales, Transactions $transactions, SteamInventoryController $steamInventoryController)
    {
        if (!$request->input('sell_list_items_obj_str')) {
            return response()->json([
                'success' => 0,
                'error' => 'Invalid post data.'
            ]);
        }

        // decode our json object
        $client_items = json_decode($request->input('sell_list_items_obj_str'), true);
        
        if (!is_array($client_items)) {
            return [
                'success' => 0,
                'error' => 'Data integrity check failed (1).'
            ];
        }

        // we need to get additional item data from their cached inventory
        $inventory = Cache::get('steam_inventory:app_id:'.getAppId().':uid:'.Auth::id());
        $steam_id = Auth::user()->getSteamId();

        if ($inventory == null) {

            // inventory not cached, fetch from steam instead

            $raw_inventory = $steamInventoryController->getRawInventory($steam_id);

            if (empty($raw_inventory)) {
                return [
                    'success' => 0,
                    'error' => 'Steam is down, please try again later.'
                ];
            }

            $prepared_items = $steamInventoryController->prepareItems($steam_id, $raw_inventory);

            if (empty($prepared_items) || !isset($prepared_items['items'])) {
                return [
                    'success' => 0,
                    'error' => 'Your inventory is empty (1).'
                ];
            }

            $inventory = $prepared_items['items'];

        } else {

            // cached inventory is in JSON format
            $inventory = json_decode($inventory, true);
        }

        if (empty($inventory) || !is_array($inventory)) {
            return [
                'success' => 0,
                'error' => 'Your inventory is empty (2).'
            ];
        }

        // sort asset ids from highest to lowest
        // this is important for when matching new asset ids on the node js side
        usort($client_items, function($a, $b) {
            return $a['assetid'] <=> $b['assetid'];
        });

        $user = Auth::user();

        $user_id = Auth::id();
        $group_id = Auth::user()->getGroupId();
        $balance = $user->wallet_balance->getBalance();
        $trade_url = Auth::user()->getTradeUrl();

        if (empty($trade_url)) {
            return [
                'success' => 0,
                'error' => 'Please set your <a href="/settings" target="_blank">Trade URL</a> first.'
            ];
        }

        $s_opts_cost = 0.00;
        $boost_cost = config('app.boost_price');
        $private_1hr_cost = 0.10;
        $private_24hr_cost = 0.30;
        $private_1wk_cost = 1.25;

        $items = [];
        $ik = 0; // item key

        // do data integrity checks on received obj
        foreach ($client_items as $item) {
            
            if (
                !isset($item['assetid'])
                || !isset($item['market_name'])
                || !isset($item['quantity'])
                || !isset($item['commodity'])
                || !isset($item['price'])
                || !isset($item['sale_option'])
            ) {
                return  [
                    'success' => 0,
                    'error' => 'Data integrity check failed (2).'
                ];
            }

            // data we got from client side (untrusted)
            $client_asset = $item['assetid'];
            $client_market_name = $item['market_name'];
            $client_quantity = $item['quantity'];
            $client_cmmdty = $item['commodity'];
            $price = $item['price'];
            $s_opt = $item['sale_option'];

            if (!validAssetId($client_asset)) {
                return  [
                    'success' => 0,
                    'error' => 'Data integrity check failed (3).'
                ];
            }

            // Cached Data Key is asset id for single items
            $cdk = $client_asset;

            // Cached Data Key is market name for cmmdty items
            if ($client_cmmdty) {
                $cdk = $client_market_name;
            }

            if (!isset($inventory[$cdk])) {
                return [
                    'success' => 0,
                    'error' => 'Data integrity check failed (4).'
                ];
            }

            $name = $inventory[$cdk]['name'];
            $stkrs = $inventory[$cdk]['stkrs'];

            $quantity = count($inventory[$cdk]['asset']);

            if ($stkrs != '') {
                $stkrs = json_encode($inventory[$cdk]['stkrs'], JSON_FORCE_OBJECT|JSON_UNESCAPED_SLASHES);
            }

            $case_limit = 50;
            $cache_minutes = 5;

            $item_cmmdty = 0;

            if ($quantity > 1) {
                $item_cmmdty = 1;
            }

            /* Handle limiting of commodity items */
            if ($item_cmmdty) {

                $num_of_cases = Cache::remember('cmmdty_sale_limits:name:'.$name, $cache_minutes, function () use (&$itemSales,&$name) {
                    return $itemSales->where(['name' => $name])->count();
                });

                $quantity_limit_check = $num_of_cases + $quantity;

                $space_left = $case_limit - $num_of_cases;

                $space_left_text = 'Please try again later';

                if ($space_left > 0) {
                    $space_left_text = 'We can only accept '.$space_left.' more';
                }

                // do we already have maximum cases or does the sell quantity exceed case limit?
                if (($num_of_cases > $case_limit) || ($quantity_limit_check > $case_limit)) {

                    return response()->json([
                        'success' => 0,
                        'error' => 'We currently have '.$num_of_cases.' quantity of <br><b>'. $name. '</b><br> '.$space_left_text.'.'
                    ]);

                } else {

                    // clear cache so that we have an accurate count next deposit attempt
                    Cache::forget('cmmdty_sale_limits:name:'.$name);
                }
            }

            if (!validItemPrice($price)) {
                return [
                    'success' => 0,
                    'error' => 'Data integrity check failed (5).'
                ];
            }

            // 'asset' can contain multiple assets separated by a comma (cmmdty item)
            // we will make it an array so we can work with it
            $asset_array = $inventory[$cdk]['asset'];

            // can't sell more than what you have...
            if ($client_quantity <= $quantity) {

                $i = 0;

                foreach ($asset_array as $asset_id) {

                    if ($item_cmmdty) {

                        // don't add more than quantity
                        if ($i == $client_quantity) {
                            break;

                        } else {

                            $asset_id_key = array_search($asset_id, $inventory[$cdk]['asset']);

                            // we should unset the asset_id so users can sell cmmdty items with different prices
                            // if this is not done, multiples of the same cmmdty items will be sent to user for trade
                            unset($inventory[$cdk]['asset'][$asset_id_key]);
                        }

                        $i++;
                    }

                    $grd = $inventory[$cdk]['grd'];

                    if ($this->app_id == 0) {
                        $this->app_id = $inventory[$cdk]['app'];
                    }

                    $items['items'][$ik]['asset'] = "$asset_id";
                    $items['items'][$ik]['app'] = $inventory[$cdk]['app'];
                    $items['items'][$ik]['cid'] = $inventory[$cdk]['cid'];
                    $items['items'][$ik]['icon'] = $inventory[$cdk]['icon'];
                    $items['items'][$ik]['name'] = $name;
                    $items['items'][$ik]['color'] = $inventory[$cdk]['color'];
                    $items['items'][$ik]['ext'] = $inventory[$cdk]['ext'];
                    $items['items'][$ik]['grd'] = $grd;
                    $items['items'][$ik]['grd_id'] = $this->itemGradeId($grd);
                    $items['items'][$ik]['stkrs'] = $stkrs;
                    $items['items'][$ik]['price'] = $price;
                    $items['items'][$ik]['s_opt'] = $s_opt;
                    $items['items'][$ik]['inspct'] = $inventory[$cdk]['inspct'];
                    $items['items'][$ik]['type'] = $this->itemTypeId($grd);
                    $items['items'][$ik]['slot'] = $inventory[$cdk]['slot'];
                    $items['items'][$ik]['cat'] = $inventory[$cdk]['cat'];
                    $items['items'][$ik]['hero'] = $inventory[$cdk]['hero'];
                    $items['items'][$ik]['cmmdty'] = $item_cmmdty;

                    $ik++;
                }

                if ($s_opt == 'boost') {
                    $s_opts_cost += $boost_cost;
                }

                /*else if ($s_opt == 'private_1hr') {

                    $s_opts_cost += $private_1hr_cost;

                } else if ($s_opt == 'private_24hr') {

                    $s_opts_cost += $private_24hr_cost;

                } else if ($s_opt == 'private_1wk') {

                    $s_opts_cost += $private_1wk_cost;

                }
                */

                else if ($s_opt != '') {
                    // none of our options and not blank, must be tampered data
                    return  [
                        'success' => 0,
                        'error' => 'Data integrity check failed (6).'
                    ];
                }

            }

        }

        if (empty($items)) {
            return response()->json([
                'success' => 0,
                'error' => 'Something went wrong (1).'
            ]);
        }

        $max_num_of_sell_list_items = 30;
        $total_item_count = count($items['items']);

        if ($total_item_count > $max_num_of_sell_list_items) {
            return response()->json([
                'success' => 0,
                'error' => 'Maximum of '.$max_num_of_sell_list_items.' items per deposit.'
            ]);
        }

        // continue only if user has enough balance to cover sale options costs
        if ($balance >= $s_opts_cost) {

            $sale_fee = config('app.standard_sale_fee');

            // if it's a pro or staff apply the pro sale fee instead
            if (($group_id == config('app.pro_gid')) || (isStaff($group_id))) {
                $sale_fee = config('app.pro_sale_fee');
            }

            // select a bot (type = 0 are all the bots that holds items listed for sale)

            $max_bot_item_count = getInventoryLimit($this->app_id) - $total_item_count;
            $bot_id = DB::table('bots')->where('active', 1)->where($this->app_id.'_count', '<', $max_bot_item_count)->limit(1)->value('id');

            if ($bot_id == null)
            {
                return response()->json([
                    'success' => 0,
                    'error' => 'Bots are full, please check back later.'
                ]);
            }

            // add additional details for node.js processing
            $items['bot_id'] = $bot_id;
            $items['item_count'] = $total_item_count;
            $items['type'] = 'take';
            $items['trade_url'] = config('app.trade_url_prefix').$trade_url;
            $items['uid'] = $user_id;
            $items['fee'] = $sale_fee;

            // re-encode
            $items_encoded = json_encode($items);

            $bot_server_ip = getBotServerIp($bot_id);

            // send it off to nodejs -- which will send sale trade offer
            $curl_json_return = curlNodeJs($items_encoded, $bot_server_ip);

            if (!empty($curl_json_return)) {

                $curl_json_return_decoded = json_decode($curl_json_return, true);

                if (isset($curl_json_return_decoded['success']) && isset($curl_json_return_decoded['trade_id'])) {

                    $trade_id = $curl_json_return_decoded['trade_id'];

                    DB::beginTransaction();

                    try {

                        // update bot item count
                        DB::table('bots')->where('id', $bot_id)->increment($this->app_id.'_count', $total_item_count);

                        // charge the sale options fees
                        if ($s_opts_cost > 0) {

                            $user->updateBalanceForPurchase($s_opts_cost);
                            $transactions->newTransaction($user_id, config('app.boost_tid'), $s_opts_cost);
                        }

                    } catch (\Exception $e) {

                        DB::rollBack();

                        // disable trade offer
                        DB::table('trade_offers')->where('id', $trade_id)->update(['disabled' => 1]);

                        return response()->json([
                            'success' => 0,
                            'error' => 'Something went wrong, please DO NOT ACCEPT any trade offers we might have sent just now. Please try again later.'
                        ]);
                    }

                    DB::commit();

                    return $this->getTradeOffer($trade_id);
                }

                return $curl_json_return;

            } else {

                return response()->json([
                    'success' => 0,
                    'error' => 'Bots are down, please check back later.'
                ]);
            }

        } else {
            return response()->json([
                'success' => 0,
                'error' => 'Your sale options cost: $'.number_format($s_opts_cost, 2).'<br>Your wallet balance: $'.$balance
            ]);
        }

    }

    protected function getTradeOffer($trade_id)
    {
        $user_id = Auth::id();

        $trade_offer = DB::table('trade_offers')->where(['id' => $trade_id, 'user_id' => $user_id])->select('bot_id', 'auth_token')->first();

        if (!empty($trade_offer)) {

            $trade_offer_bot_id = $trade_offer->bot_id;
            $trade_offer_auth_token = $trade_offer->auth_token;

            return view('sections.trade_offer_sent',
                            [
                                'trade_id' => $trade_id,
                                'trade_offer_bot_id' => $trade_offer_bot_id,
                                'trade_offer_auth_token' => $trade_offer_auth_token
                            ]
                        );

        } else {

            return 0;
        }
    }

    protected function getSuggestedPrice(Request $request)
    {
        if ($request->input('suggested_price_item_name')) {

            $item_name = $request->input('suggested_price_item_name');
            $app_id = getAppId();

            $cache_minutes = 60;

            $db_suggested_price =
                Cache::remember('suggested_price:'.$item_name, $cache_minutes, function () use($item_name, $app_id) {
                    return DB::table($app_id.'_prices')->where('market_name', $item_name)->value('price');
                });

            if ($db_suggested_price != '') {
                return response($db_suggested_price);
            }
        }

        return response('not_found');
    }

    function itemTypeId ($grade)
    {
        if (strpos($grade, 'Knife') !== false)
            return 1;
        else if (strpos($grade, 'Pistol') !== false)
            return 2;
        else if (strpos($grade, 'Rifle') !== false)
            return 3;
        else if (strpos($grade, 'Shotgun') !== false)
            return 4;
        else if (strpos($grade, 'SMG') !== false)
            return 5;
        else if (strpos($grade, 'Machine Gun') !== false)
            return 6;
        else if (strpos($grade, 'Container') !== false)
            return 7;
        else if (strpos($grade, 'Key') !== false)
            return 8;
        else if (strpos($grade, 'Sticker') !== false)
            return 9;
        else if (strpos($grade, 'Gloves') !== false)
            return 10;
        else if (strpos($grade, 'Graffiti') !== false)
            return 11;
        else
            return 0;
    }

    function itemGradeId ($grade)
    {
        if (strpos($grade, 'Consumer') !== false)
            return 1;
        else if (strpos($grade, 'Industrial') !== false)
            return 2;
        else if (strpos($grade, 'Mil-Spec') !== false)
            return 3;
        else if (strpos($grade, 'Restricted') !== false)
            return 4;
        else if (strpos($grade, 'Classified') !== false)
            return 5;
        else if (strpos($grade, 'Covert') !== false)
            return 6;
        else if (strpos($grade, 'Exceedingly Rare') !== false)
            return 7;
        else
            return 0;
    }

}