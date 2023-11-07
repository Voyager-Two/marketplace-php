<?php

namespace App\Http\Controllers;
use App\Models\ItemSales;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Transactions;

class ManageSalesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('throttle:30,1')->only('ajaxPost');
    }

    public function index(ItemSales $itemSales, Request $request)
    {
        $user_id = Auth::id();
        $time_zone = Auth::user()->getTimeZone();

        $display_sort = 'newest';
        $asc_or_desc = 'desc';

        if ($request->input('sort') == 'o') {
            $display_sort = 'oldest';
            $asc_or_desc = 'asc';
        }

        $display_filter = 'all';

        if ($request->input('filter') == 'on-sale') {
            $display_filter = 'on-sale';
        }

        if ($request->input('filter') == 'sold') {
            $display_filter = 'sold';
        }

        if ($request->input('filter') == 'cancelled') {
            $display_filter = 'cancelled';
        }

        $games = getGames();

        $display_game = 'all';
        $request_app_id = 0;

        if ($request->input('game') > 0 && isset($games[$request->input('game')])) {
            $request_app_id = $request->input('game');
            $display_game = getGameShortName($request_app_id);
        }

        $db_sales =
            $itemSales
                ->where('item_sales.user_id', $user_id)
                ->orderBy('item_sales.id', $asc_or_desc)
                ->when($request_app_id != 0, function ($query) use($request_app_id) {
                    return $query->where('item_sales.appid', $request_app_id);
                })
                ->when($request->input('filter') == 'on-sale', function ($query) {
                    return $query->where('item_sales.status', config('app.sale_active'));
                })
                ->when($request->input('filter') == 'sold', function ($query) {
                    return $query->where('item_sales.status', config('app.sale_sold'));
                })
                ->when($request->input('filter') == 'cancelled', function ($query) {
                    return $query->where('item_sales.status', config('app.sale_cancelled'));
                })
                ->leftJoin('trade_offers AS to', function ($query) {
                    $query->where('item_sales.status', config('app.sale_cancelled'))
                            ->on('item_sales.trade_id', '=', 'to.id')
                            ->where('to.type', 2);
                })
                ->select(
                    'to.status AS trade_status',
                    'item_sales.id',
                    'item_sales.name',
                    'item_sales.exterior',
                    'item_sales.status',
                    'item_sales.price',
                    'item_sales.boost',
                    'item_sales.time'
                )
                ->paginate(20);

        $user_sales = [];

        foreach ($db_sales as $sale) {

            $exterior_title = '';

            if ($sale->exterior != 0) {

                $market_name = $sale->name . ' (' . getExteriorTitle($sale->exterior) . ')';
                $exterior_title = getExteriorTitle($sale->exterior);

            } else {
                $market_name = $sale->name;
            }

            if (!isset($sale->trade_status)) {
                $sale->trade_status = 0;
            }

            $user_sales[] = [

                'id' => $sale->id,
                'market_name' => $market_name,
                'name' => $sale->name,
                'exterior' => $sale->exterior,
                'exterior_title' => $exterior_title,
                'status' => $sale->status,
                'trade_status' => $sale->trade_status,
                'price' => $sale->price,
                'boost' => $sale->boost,
                'time' => $sale->time,
                'time_display' => parseTime($sale->time, $time_zone, 'dateTime'),

            ];
        }

        return view('pages.manage_sales',
            [
                'user_sales' => $user_sales,
                'db_sales' => $db_sales,
                'display_sort' => $display_sort,
                'display_filter' => $display_filter,
                'games' => $games,
                'display_game' => $display_game
            ]
        );
    }

    public function ajaxPost (Request $request, ItemSales $itemSales, Transactions $transactions, DeliveryController $deliveryController)
    {
        $this->validate($request, [
            'sale_id' => 'required|integer'
        ]);

        $sale_id = $request->input('sale_id');

        if (Auth::check()) {

            $user = Auth::user();
            $user_id = Auth::id();

            if ($request->input('new_price')) {

                $this->validate($request, [
                    'new_price' => 'required|numeric'
                ]);

                $new_price = $request->input('new_price');

                if (validItemPrice($new_price)) {

                    $result = $user->updateSalePrice($sale_id, $new_price);

                    if ($result) {
                        return response()->json([
                            'status' => 1
                        ]);
                    }
                }

            } else if ($request->input('boost_item')) {

                $sale = $user->boostSale($sale_id, $transactions);

                if ($sale) {

                    // sale boosted

                    return response()->json([
                        'status' => 1
                    ]);

                } else {

                    return response()->json([
                        'status' => 0
                    ]);
                }

            } else if ($request->input('cancel_sale')) {

                $sale = $user->cancelSale($sale_id);

                if ($sale) {

                    // sale cancelled

                    $sale =
                        $itemSales
                            ->where('id', $sale_id)
                            ->select('id', 'bot_id', 'assetid', 'appid', 'contextid')
                            ->first();

                    if ($sale != null) {

                        $sale_ids_array = [];
                        array_push($sale_ids_array, $sale_id);

                        $send_results = $deliveryController->sendItems($sale_ids_array, 'cancel');

                        $complete_success = $send_results['complete_success'];

                        if ($complete_success) {

                            return response()->json([
                                'status' => 1
                            ]);

                        } else {

                            return response()->json([
                                'status' => 2
                            ]);
                        }
                    }

                } else {

                    // item already sold
                    return response()->json([
                        'status' => 3
                    ]);

                }

            } else if ($request->input('cancelled_sale_send_offer')) {

                // INNER JOIN trade_offers AS trdo ON itms.trade_id = trdo.id AND trdo.type = 2 AND trdo.status != 1
                $sale = DB::select(
                    'SELECT itms.id, itms.bot_id, itms.assetid, itms.appid, itms.contextid FROM item_sales AS itms 
                      WHERE itms.id = ? AND itms.user_id = ? AND itms.status = ?
                      ',
                    [$sale_id, $user_id, config('app.sale_cancelled')]
                );

                if ($sale != null) {

                    $sale_ids_array = [];
                    array_push($sale_ids_array, $sale_id);

                    $send_results = $deliveryController->sendItems($sale_ids_array, 'cancel');

                    $complete_success = $send_results['complete_success'];

                    if ($complete_success) {

                        return response()->json([
                            'status' => 1
                        ]);

                    } else {

                        return response()->json([
                            'status' => 2
                        ]);
                    }

                } else {

                    return response()->json([
                        'status' => 0,
                        'error' => 'Sale not found'
                    ]);
                }

            } else {

                return response()->json([
                    'status' => 0,
                    'error' => 'Invalid request'
                ]);
            }

        }

        return response('', 401);
    }

}
