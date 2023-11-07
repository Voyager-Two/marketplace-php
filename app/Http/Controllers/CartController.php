<?php

namespace App\Http\Controllers;
use App\Models\Cart;
use App\Models\ItemSales;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use App\Mail\PurchaseReceipt;
use App\Mail\SalesReceipt;

class CartController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth')->only('index,post');
        $this->middleware('throttle:25,1');
    }

    public function index(SalesController $sales, Cart $cartItems)
    {
        $user_id = Auth::id();

        $itemsForSale =
            $cartItems->where('cart_items.user_id', $user_id)
                ->join('item_sales', 'cart_items.sale_id', '=', 'item_sales.id')
                ->where('item_sales.status', '=', config('app.sale_active'))
                ->orderBy('cart_items.id', 'dsc')
                ->select('item_sales.*')
                ->get();

        $itemsForSaleDisplay = $sales->parseItemsForSale($itemsForSale);

        $cart_sale_ids = [];

        foreach ($itemsForSale as $item) {
            array_push($cart_sale_ids, $item->id);
        }

        // remove sold items from cart

        $removed_cart_item_names =
            $cartItems->where('cart_items.user_id', $user_id)
                ->join('item_sales', 'cart_items.sale_id', '=', 'item_sales.id')
                ->whereNotIn('cart_items.sale_id', $cart_sale_ids)
                ->select('item_sales.id', 'item_sales.name', 'item_sales.exterior')->get();

        if ($removed_cart_item_names != null) {
            $cartItems->where('user_id', $user_id)->whereNotIn('sale_id', $cart_sale_ids)->delete();
            Cache::forget('cart_count:uid:'.Auth::id());
            Cache::forget('cart_cost:uid:'.Auth::id());
        }

        return view(
            'pages.cart',
            [
                'itemsForSaleDisplay' => $itemsForSaleDisplay,
                'cart_sale_ids' => $cart_sale_ids,
                'removed_cart_item_names' => $removed_cart_item_names
            ]);
    }

    public function ajaxPost(Request $request, ItemSales $itemSales, Cart $cart, DeliveryController $deliveryController)
    {
        if ($request->input('sale_id')) {

            $sale_id = $request->input('sale_id');

            if (Auth::check()) {

                $user_id = Auth::id();

                $sold = $itemSales->getSold($sale_id, $user_id);

                if ($sold == 0) {

                    $already_in_cart_check = $cart->where(['user_id' => $user_id, 'sale_id' => $sale_id])->count();

                    if ($already_in_cart_check == 0) {

                        $cart_item_count = $cart->where(['user_id' => $user_id])->count();

                        if ($cart_item_count >= config('app.max_cart_count')) {

                            return response()->json([
                                'status' => 0,
                                'error' => 'Max '.config('app.max_cart_count').' items'
                            ]);

                        } else {

                            $cart->add($sale_id);
                        }

                    } else {

                        $cart->remove($sale_id);
                    }

                    return response()->json([
                        'status' => 1
                    ]);

                } else {

                    return response()->json([
                        'status' => 0,
                        'error' => 'Item already sold.'
                    ]);
                }

            } else {

                return response()->json([
                    'status' => 0,
                    'error' => 'Please sign in first.'
                ]);
            }

        } elseif ($request->input('checkout')) {

            if (Auth::check()) {

                $user_id = Auth::id();
                $trade_url = Auth::user()->getTradeUrl();

                if (empty($trade_url)) {
                    return response()->json([
                        'status' => 0,
                        'error' => 'Please set your <a href="/settings" target="_blank">Trade URL</a> first.'
                    ]);
                }

                DB::beginTransaction();

                try
                {
                    $sales =
                        $cart->where('cart_items.user_id', $user_id)
                            ->join('item_sales AS is', 'cart_items.sale_id', '=', 'is.id')
                            ->where('is.status', '=', config('app.sale_active'))
                            ->join('users AS u', 'is.user_id', '=', 'u.id')
                            /*
                            ->leftJoin('referrals AS rf', function ($query) {
                                $query->where('u.referral_status', 1)
                                    ->on('u.id', '=', 'rf.user_id')
                                    ->where('rf.sale_commission_amount', '<=', config('app.max_referral_sale_commission'));
                            })
                            */
                            ->select(
                                'u.id AS user_id',
                                'u.username',
                                'u.email',
                                'u.send_sales_receipts',
                                /*
                                'rf.referral_user_id',
                                'rf.sale_commission_amount',
                                */
                                'is.price',
                                'is.fee',
                                'is.status',
                                'is.name',
                                'is.exterior',
                                'cart_items.sale_id'
                            )
                            ->lockForUpdate() // make sure [prices, status, fee] doesn't change before transaction is complete
                            ->get();

                    $sale_ids_array = [];
                    $cart_total_count = 0;
                    $purchase_amount = 0;

                    foreach ($sales as $sale) {
                        $cart_total_count++;
                        $purchase_amount += $sale->price;
                        array_push($sale_ids_array, $sale->sale_id);
                    }

                    if ($cart_total_count >= config('app.max_cart_count')) {

                        return response()->json([
                            'status' => 0,
                            'error' => 'Maximum ' . config('app.max_cart_count') . ' items per purchase.<br>You have ' . $cart_total_count . ' items in your cart.'
                        ]);
                    }

                    if ($cart_total_count != $request->input('item_count') || $purchase_amount != $request->input('total_cost')) {

                        return response()->json([
                            'status' => 0,
                            'error' => 'Some items in your cart were sold, please refresh.'
                        ]);
                    }

                    $balance = Auth::user()->wallet_balance->getBalance();

                    $balance_output = priceOutput($balance);

                    // we do not need to worry about concurrency here because balance cannot be negative, will raise an exception
                    // so if they cannot truly afford it, purchase will not go through
                    if ($balance < $purchase_amount) {

                        return response()->json([
                            'status' => 0,
                            'error' => 'Insufficient wallet balance: '.$balance_output
                        ]);
                    }

                    // update each item sale status to sold
                    $itemSales->whereIn('id', $sale_ids_array)->update(['status' => config('app.sale_sold')]);

                    /*** Handle Buyer ***/

                    // insert new transaction
                    DB::table('transactions')
                        ->insert(
                            [
                                'user_id' => $user_id,
                                'tid' => config('app.cart_checkout_tid'),
                                'amount' => $purchase_amount,
                                'time' => time()
                            ]
                        );

                    // update buyer wallet to reflect purchase
                    Auth::user()->updateBalanceForPurchase($purchase_amount);

                    // insert purchases for each sale for buyer
                    $purchase_insert = [];

                    foreach ($sales as $sale) {

                        array_push($purchase_insert,
                            [
                                'user_id' => $user_id,
                                'sale_id' => $sale->sale_id,
                                'time' => time()
                            ]
                        );
                    }

                    DB::table('item_purchases')->insert($purchase_insert);

                    // delete cart items for buyer
                    DB::table('cart_items')->where('user_id', $user_id)->delete();
                    Cache::forget('cart_count:uid:'.Auth::id());
                    Cache::forget('cart_cost:uid:'.Auth::id());

                    /*** Handle Sellers ***/

                    $i = 0;
                    $sellers = [];

                    $purchase_receipt_sales = [];
                    $buyer_wants_receipt = Auth::user()->getSendPurchaseReceipts();

                    // group sales together for each seller
                    foreach ($sales as $sale) {

                        $sellers[$sale->user_id]['username'] = $sale->username;
                        $sellers[$sale->user_id]['email'] = $sale->email;
                        $sellers[$sale->user_id]['send_sales_receipts'] = $sale->send_sales_receipts;

                        $sellers[$sale->user_id]['sales'][$i]['sale_id'] = $sale->sale_id;
                        $sellers[$sale->user_id]['sales'][$i]['name'] = $sale->name .' '. $sale->exterior != 0 ? '<span title="'.getExteriorTitle($sale->exterior).'">('.getExteriorTitleAbbr($sale->exterior).')</span>' : $sale->name;
                        $sellers[$sale->user_id]['sales'][$i]['price'] = $sale->price;
                        $sellers[$sale->user_id]['sales'][$i]['fee'] = $sale->fee;

                        if ($buyer_wants_receipt) {
                            $purchase_receipt_sales[$i]['sale_id'] = $sale->sale_id;
                            $purchase_receipt_sales[$i]['name'] = $sale->name;
                            $purchase_receipt_sales[$i]['price'] = $sale->price;
                        }

                        /*
                        if (isset($sale->referral_user_id)) {
                            // referral commission
                            $sellers[$sale->user_id]['referral_user_id'] = $sale->referral_user_id;
                            $sellers[$sale->user_id]['sale_commission_amount'] = $sale->sale_commission_amount;
                        }
                        */

                        $i++;
                    }

                    $total_commission_charged = 0;

                    // loop through each seller
                    foreach ($sellers as $seller_user_id => $_seller) {

                        $_sales = $_seller['sales'];
                        $seller_transactions = [];
                        $total_credit_amount = 0;
                        $referral_commission = 0;

                        // loop through each sale for one seller
                        foreach ($_sales as $_sale) {

                            $sale_fee = $_sale['price'] * ($_sale['fee'] / 100);

                            // minimum sale fee is 1 cent
                            $sale_fee = ($sale_fee < 0.01) ? 0.01 : $sale_fee;

                            $sale_credit = ($_sale['price'] - $sale_fee);

                            $total_credit_amount += $sale_credit;

                            $referral_commission += $sale_fee;
                            $total_commission_charged += $sale_fee;

                            array_push($seller_transactions,
                                [
                                    'user_id' => $seller_user_id,
                                    'tid' => config('app.item_sale_tid'),
                                    'sale_id' => $_sale['sale_id'],
                                    'amount' => $sale_credit,
                                    'credit' => 1,
                                    'time' => time()
                                ]
                            );
                        }

                        // insert a transactions for seller
                        DB::table('transactions')->insert($seller_transactions);

                        $sellers[$seller_user_id]['total_credit_amount'] = $total_credit_amount;

                        // update seller wallet balance
                        DB::table('wallet_balances')->where('user_id', $seller_user_id)->increment('balance', $total_credit_amount);

                        /*
                        // handle referrals
                        if (isset($sellers[$seller_user_id]['referral_user_id'])) {

                            // referral commission can't be more than maximum commission amount

                                // current amount
                                $current_sale_commission_amount = $sellers[$seller_user_id]['sale_commission_amount'];

                                // max amount
                                $max_commission_amount = config('app.max_referral_sale_commission');

                                // (SaleCommission + CurrentCommission) > MAX ... SaleCommission = (MAX - CurrentCommission)
                                if (($referral_commission + $current_sale_commission_amount) > config('app.max_referral_sale_commission')) {
                                    $referral_commission = ($max_commission_amount - $current_sale_commission_amount);
                                }

                            // subtract referral commission from total commission our company has earned (from this purchase)
                            $total_commission_charged -= $referral_commission;

                            // credit referral commission
                            DB::table('wallet_balances')->where('user_id', $sellers[$seller_user_id]['referral_user_id'])->increment('balance', $referral_commission);

                            // insert new transaction for credited commission
                            DB::table('transactions')
                                ->insert(
                                    [
                                        'user_id' => $sellers[$seller_user_id]['referral_user_id'],
                                        'tid' => config('app.referral_credit_tid'),
                                        'amount' => $referral_commission,
                                        'credit' => 1,
                                        'time' => time()
                                    ]
                                );

                            // update total sale commission amount for referred user
                            DB::table('referrals')->where('user_id', $seller_user_id)->increment('sale_commission_amount', $referral_commission);
                        }
                        */
                    }

                    if ($total_commission_charged > 0) {

                        $sale_commissions_insert = [
                            'user_id' => $user_id,
                            'amount' => $total_commission_charged,
                            'time' => time()
                        ];

                        DB::table('sale_commissions')->insert($sale_commissions_insert);
                    }

                } catch (\Exception $e) {

                    DB::rollBack();

                    return response()->json([
                        'status' => 0,
                        'error' => 'Something went wrong, please try again soon.'
                    ]);
                }

                DB::commit();

                // send items via trade offers

                $send_results = $deliveryController->sendItems($sale_ids_array);

                $partial_success = $send_results['partial_success'];
                $complete_success = $send_results['complete_success'];

                $msg = 'We were unable to send any trade offers.';

                if ($complete_success) {

                    $msg = 'All trade offers successfully sent.';

                } else if ($partial_success) {

                    $msg = 'Some trade offers were sent, but not all.';
                }

                // user will see this message when redirected to item purchases page
                session(['alert' => '<i class="icon-checkmark"></i> Purchase complete. '.$msg]);

                // good to do this last because we might get stuck while sending emails and they are not essential
                $this->sendEmails($purchase_receipt_sales,$sellers,$purchase_amount);

                return response()->json([
                    'status' => 1
                ]);
            }

        } else {

            return response()->json([
                'status' => 0,
                'error' => 'Please sign in first.'
            ]);
        }

        return response('', 401);
    }

    public function sendEmails ($purchase_receipt_sales,$sellers,$purchase_amount)
    {
        // does the buyer want a sales receipt?
        if (Auth::user()->getSendPurchaseReceipts()) {

            // send purchase receipt email
            Mail::to(Auth::user()->getEmail())->queue(
                new PurchaseReceipt(Auth::user()->getUsername(), $purchase_amount, $purchase_receipt_sales, time())
            );
        }

        // loop through each seller
        foreach ($sellers as $seller_user_id => $_seller) {

            // does the seller want a sales receipts?
            if ($_seller['send_sales_receipts']) {

                // send sales receipt email
                Mail::to($_seller['email'])->queue(
                    new SalesReceipt($_seller['username'], $_seller['total_credit_amount'], $_seller['sales'], time())
                );
            }
        }
    }

    public function post(Request $request, Cart $cart)
    {
        if ($request->input('clear_cart')) {

            $user_id = Auth::id();

            $cart->where('user_id', $user_id)->delete();
            Cache::forget('cart_count:uid:'.Auth::id());
            Cache::forget('cart_cost:uid:'.Auth::id());

            return redirect()->route('home')->with('alert', 'Cart cleared.');
        }

        return redirect()->route('cart');
    }

}