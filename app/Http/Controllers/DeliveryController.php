<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    protected $delivery_type = 'purchase';

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('throttle:10,1')->only('resendAll');
    }

    public function resendAll ()
    {
        $user_id = Auth::id();

        // purchases where trade offer was never sent (trade_id is set to 0)
        $never_sent =
            DB::table('item_purchases AS ip')
                ->where(['ip.user_id' => $user_id, 'ip.trade_id' => 0])
                ->select('ip.sale_id')
                ->get();

        // purchases where trade offer was sent but the type is 1 (give offer) and status is not 1 (offer was accepted)
        $cancelled_or_expired =
            DB::table('item_purchases AS ip')
                ->where('ip.user_id', $user_id)
                ->where('ip.trade_id', '!=', 0)
                ->join('trade_offers AS to', 'to.id', '=', 'ip.trade_id')
                ->where('to.type', 1)
                ->where('to.status', '!=', 1)
                ->select('ip.sale_id')
                ->get();

        if ($never_sent == null && $cancelled_or_expired == null) {
            return redirect()->route('wallet.item_purchases')->with('alert', 'You don\'t have any undelivered items.');
        }

        $sale_ids_array = [];

        foreach ($never_sent as $sale) {
            array_push($sale_ids_array, $sale->sale_id);
        }

        foreach ($cancelled_or_expired as $sale) {
            array_push($sale_ids_array, $sale->sale_id);
        }

        $send_results = $this->sendItems($sale_ids_array);

        $partial_success = $send_results['partial_success'];
        $complete_success = $send_results['complete_success'];

        // zero success, bots are probably down
        if (!$partial_success && !$complete_success) {
            return redirect()->route('wallet.item_purchases')->with('alert', '<i class="icon-x"></i> Bots are down, please try again later.');
        }

        if ($complete_success) {

            // all offers were sent
            return redirect()->route('wallet.item_purchases')->with('alert', '<i class="icon-ok"></i> All offers sent!<br>It may take up to 30 seconds to receive them.');

        } else {

            // some or all offers failed to be sent
            return redirect()->route('wallet.item_purchases')->with('alert', '<i class="icon-ok"></i> Some offers sent, but not all.<br>It may take up to 30 seconds to receive them.');
        }

    }

    public function prepareItems ($sale_ids)
    {
        $sales =
            DB::table('item_sales')
                ->whereIn('item_sales.id', $sale_ids)
                ->select('item_sales.id', 'item_sales.bot_id', 'item_sales.assetid', 'item_sales.appid', 'item_sales.contextid')
                ->get();

        $item_count = $sales->count();

        if ($item_count) {

            $user_id = Auth::id();
            $trade_url = config('app.trade_url_prefix').Auth::user()->getTradeUrl();

            $arr = [];

            $i = 0;
            $bot_id = 0;

            foreach ($sales as $sale) {

                // reset i count for each bot
                if ($bot_id != $sale->bot_id) {
                    $i = 0;
                }

                $bot_id = $sale->bot_id;
                $bot_server_ip = getBotServerIp($bot_id);

                $arr[$bot_server_ip]['items'][$sale->bot_id][$i]['sale_id'] = $sale->id;
                $arr[$bot_server_ip]['items'][$sale->bot_id][$i]['assetid'] = "$sale->assetid";
                $arr[$bot_server_ip]['items'][$sale->bot_id][$i]['appid'] = $sale->appid;
                $arr[$bot_server_ip]['items'][$sale->bot_id][$i]['contextid'] = $sale->contextid;

                $i++;
            }

            $arr['meta']['action'] = 'purchase';

            if ($this->delivery_type != 'purchase') {
                $arr['meta']['action'] = 'cancel';
            }

            $arr['meta']['type'] = 'give';
            $arr['meta']['uid'] = $user_id;
            $arr['meta']['trade_url'] = $trade_url;

            return $arr;
        }

        return false;
    }

    public function sendItems ($sale_ids, $delivery_type='purchase')
    {
        $partial_success = false;
        $complete_success = false;

        if ($delivery_type != 'purchase') {
            $this->delivery_type = 'cancel';
        }

        $prepared_items = $this->prepareItems($sale_ids);

        if ($prepared_items != false) {

            $meta_info = $prepared_items['meta'];

            // unset meta info so we don't loop through it below
            unset($prepared_items['meta']);

            $complete_success = true;

            // send requests to each involving node.js servers
            foreach ($prepared_items as $server_ip => $items) {

                $details = json_encode(array_merge($meta_info, $items));

                $result = $this->sendRequest($details, $server_ip);

                if ($result === false) {

                    $complete_success = false;

                } elseif ($result === true) {

                    $partial_success = true;
                }
            }

        }

        return [
            'partial_success' => $partial_success,
            'complete_success' => $complete_success
        ];
    }

    public function sendRequest ($details, $bot_server_ip)
    {
        // send our items to node.js for processing
        $curl_json_return = curlNodeJs($details, $bot_server_ip);

        if (!empty($curl_json_return)) {

            $response = json_decode($curl_json_return, true);

            if (isset($response['success'])) {

                return true;
            }
        }

        return false;
    }

    public function botCheck ($bot_id, $bot_server_ip)
    {
        $bot_check_details = [
            'bot_id' => $bot_id,
            'type' => 'bot_check'
        ];

        $bot_check_details = json_encode($bot_check_details);

        $curl_return = curlNodeJs($bot_check_details, $bot_server_ip);

        if (!empty($curl_return)) {

            if ($curl_return === "up") {
                return true;
            }
        }

        return false;
    }

    public function repairBotItemCounts ()
    {
        $bots = DB::table('bots')->where('active', 1)->select('id')->get();
        $games = getGames();

        // loop through each bot
        foreach ($bots as $bot) {

            // loop through each app/game, as each has separate count
            foreach ($games as $app_id => $game) {

                // get active sale count for this bot
                $item_count = DB::table('item_sales')->where(['bot_id' => $bot->id, 'appid'=> $app_id, 'status' => config('app.sale_active')])->count();

                // update bot item count
                DB::table('bots')->where('id', $bot->id)->update([$app_id.'_count' => $item_count]);
            }

        }

        return true;
    }
}