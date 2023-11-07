<?php

namespace App\Http\Controllers;
use App\Models\Transactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;

class WalletController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('throttle:15,1')->only('cashoutPost');
    }

    public function index (Request $request, PayPalController $payPalController)
    {
        if ($request->input('paypal_payment') == 1) {
            $payPalController->paymentCapture($request);
        }

        $user_id = Auth::id();
        $balance = Auth::user()->wallet_balance->getBalance();
        $added_funds = Auth::user()->wallet_balance->getAddedFunds();

        $cashout_balance = number_format(($balance - $added_funds), 2);

        $cashout_requests = Auth::user()->getCashoutRequests();

        $bitpay_orders =
            DB::table('payment_orders')
                ->where(['user_id' => $user_id, 'source' => config('app.bitpay_payments_id'), 'status' => config('app.bitpay_status_paid')])
                ->select('amount')
                ->get();

        // return all the goodies
        return view('pages.wallet.index',
            [
                'page' => '',
                'menu_links' => $this->getMenuLinks(),
                'balance' => $balance,
                'cashout_balance' => $cashout_balance,
                'cashout_requests' => $cashout_requests,
                'bitpay_orders' => $bitpay_orders
            ]
        );
    }

    public function addFundsView (Request $request, PayPalController $payPalController)
    {
        $user_id = Auth::id();

        if ($request->input('paypal_return') && $request->input('code')) {
            $payPalController->connectPayPal($request);
        }

        $paypal_acc = DB::table('paypal_accounts')->where(['user_id' => $user_id])->value('id');
        $paypal_linked = $paypal_acc != null ? 1 : 0;

        $paypal_client_id = config('app.paypal_sandbox_client_id');

        if (App::environment('production')) {
            $paypal_client_id = config('app.paypal_client_id');
        }

        $paypal_connect_link = 'https://www.paypal.com/webapps/auth/protocol/openidconnect/v1/authorize?response_type=code&scope=openid+profile+address+https%3A%2F%2Furi.paypal.com%2Fservices%2Fpaypalattributes&client_id='.$paypal_client_id.'&redirect_uri=https%3A%2F%2F'.config('app.domain').'%2Fwallet%2Fadd_funds%3Fpaypal_return%3D1';

        if (App::environment('local')) {
            $paypal_connect_link = str_replace('www.paypal.com', 'www.sandbox.paypal.com', $paypal_connect_link);
        }

        // return all the goodies
        return view('pages.wallet.add_funds',
            [
                'page' => '',
                'menu_links' => $this->getMenuLinks(),
                'id_verified' => Auth::user()->getIdVerified(),
                'paypal_linked' => $paypal_linked,
                'paypal_connect_link' => $paypal_connect_link
            ]
        );
    }

    public function cashoutView ()
    {
        $balance = Auth::user()->wallet_balance->getBalance();
        $added_funds = Auth::user()->wallet_balance->getAddedFunds();

        $cashout_balance = number_format(($balance - $added_funds), 2);

        // return all the goodies
        return view('pages.wallet.cashout',
            [
                'page' => '',
                'menu_links' => $this->getMenuLinks(),
                'cashout_balance' => $cashout_balance,
                'id_verified' => Auth::user()->getIdVerified()
            ]
        );
    }

    public function cancelCashoutPost($request)
    {
        // cancel cashout request

        $this->validate($request, [
            'cashout_request_id' => 'required|integer',
        ]);

        $id = $request->input('cashout_request_id');

        DB::beginTransaction();

        try {

            $user_id = Auth::id();

            // has cashout request already been approved or denied?
            // lock so other users don't take action on same row
            $cashout_request =
                DB::table('cashout_requests')
                    ->where(['id' => $id, 'user_id' => $user_id])
                    ->select('user_id', 'status', 'amount')
                    ->lockForUpdate()->first();

            if ($cashout_request != null && $cashout_request->status === 0) {

                // change cashout status
                DB::table('cashout_requests')->where('id', $id)->update(['status' => config('app.cashout_request_cancelled')]);

                // refund the amount to user wallet
                DB::table('wallet_balances')
                    ->where('user_id', $cashout_request->user_id)
                    ->increment('balance', $cashout_request->amount);

                // insert new transaction for the user
                DB::table('transactions')
                    ->insert(
                        [
                            'user_id' => $cashout_request->user_id,
                            'tid' => config('app.cashout_refund_tid'),
                            'amount' => $cashout_request->amount,
                            'credit' => 1,
                            'time' => time()
                        ]
                    );

            } else {

                return redirect()->route('wallet')->with('alert', 'Failed to cancel. Cashout request status has changed.');
            }

            DB::commit();

            return redirect()->route('wallet')->with('alert', '<i class="icon-checkmark"></i> Cashout request cancelled.');

        } catch (\Exception $e) {

            DB::rollBack();
        }

        return redirect()->route('wallet')->with('alert', 'Something went wrong, please try again.');
    }

    public function cashoutPost (Request $request, Transactions $transactions)
    {
        if ($request->input('cancel_cashout')) {
            $this->cancelCashoutPost($request);
        }

        $this->validate($request, [
            'cashout_method' => 'required|integer',
            'amount' => 'required|numeric',
        ]);

        $cashout_method = $request->input('cashout_method');
        $amount = $request->input('amount');

        if ($cashout_method == config('app.paypal_cashout_tid')) {
            $this->validate($request, [
                'paypal_email' => 'required|email',
            ]);
        }

        if ($cashout_method == config('app.bitcoin_cashout_tid')) {
            $this->validate($request, [
                'bitcoin_address' => 'required|alpha_num|between:26,35',
            ]);

            //$id_verified = Auth::user()->getIdVerified();

            //if ($id_verified != 1) {
            //    return redirect()->route('wallet.cashout');
            //}
        }

        $bitcoin_address = $request->input('bitcoin_address');
        $paypal_email = $request->input('paypal_email');

        // amount cannot be less than minimum cashout setting
        if (is_numeric($amount) && ($amount < config('app.min_cashout'))) {
            return redirect()->route('wallet.cashout')->with('alert', 'Amount cannot be less than $'.config('app.min_cashout').'.');
        }

        DB::beginTransaction();

        try
        {
            $user_id = Auth::id();

            // we must lock this row until end of our transaction to prevent concurrency issues
            $wallet_balance = DB::table('wallet_balances')->where('user_id', $user_id)->select('balance', 'added_funds')->sharedLock()->first();

            $cashout_balance = number_format(($wallet_balance->balance - $wallet_balance->added_funds), 2, '.', '');

            // amount requested cannot be more than cashout balance
            if ($amount > $cashout_balance) {
                return redirect()->route('wallet.cashout')->with('alert', 'Amount cannot be more than balance.');
            }

            $send_address = '';
            $transaction_id = -1;

            // update user paypal email or bitcoin address
            if ($cashout_method == config('app.paypal_cashout_tid')) {

                $send_address = $paypal_email;

                $transaction_id = config('app.paypal_cashout_tid');
                Auth::user()->paypal_email = $paypal_email;
                Auth::user()->save();

            } else if ($cashout_method == config('app.bitcoin_cashout_tid')) {

                $send_address = $bitcoin_address;

                $transaction_id = config('app.bitcoin_cashout_tid');
                Auth::user()->bitcoin_address = $bitcoin_address;
                Auth::user()->save();
            }

            // update user balance
            Auth::user()->wallet_balance()->decrement('balance', $amount);

            // insert new cashout request
            $cashout_request_id =
                DB::table('cashout_requests')
                ->insertGetId(
                    [
                        'user_id' => $user_id,
                        'method' => $cashout_method,
                        'amount' => $amount,
                        'send_address' => $send_address,
                        'time' => time()
                    ]
                );

            $transactions->newTransaction($user_id, $transaction_id, $amount, $cashout_request_id);

        } catch (\Exception $e) {

            DB::rollBack();
            return redirect()->route('wallet.cashout')->with('alert', 'Something went wrong.');
        }

        DB::commit();

        return redirect()->route('wallet')->with('alert', '<i class="icon-checkmark"></i> Cashout request received. You will receive your funds in 2 to 18 hours.');
    }

    public function viewTransactions (Transactions $transactions)
    {
        $user_id = Auth::id();
        $time_zone = Auth::user()->getTimeZone();

        $db_transactions =
            $transactions
                ->where('transactions.user_id', $user_id)
                ->leftJoin('item_sales AS is', 'transactions.sale_id', '=', 'is.id')
                ->leftJoin('cashout_requests AS cr', 'transactions.cashout_request_id', '=', 'cr.id')
                ->orderBy('transactions.id', 'transactions.desc')
                ->select('transactions.*', 'is.id AS sale_id', 'is.name', 'is.exterior', 'cr.send_address')
                ->paginate(20);

        $user_transactions = [];

        foreach ($db_transactions as $transaction) {

            $title = getTransactionTitle($transaction->tid);

            // we should modify the transaction titles to give details to user

            if ($transaction->tid == config('app.item_sale_tid')) {

                $exterior = $transaction->exterior;

                $exterior_title = $exterior != 0 ? ' ('.getExteriorTitleAbbr($exterior).')' : '';

                $title =
                    '<span class="hint--top-right" aria-label="'.$transaction->name.$exterior_title.'">
                        <a href="/sale/'.$transaction->sale_id.'">Sale Credit</a>
                    </span>
                ';

            } elseif ($transaction->tid == config('app.paypal_cashout_tid') || $transaction->tid == config('app.bitcoin_cashout_tid')) {

                $title = '<span class="hint--top-right" aria-label="'.$transaction->send_address.'">'.$title.'</span>';
            }

            $user_transactions[] = [

                'id' => $transaction->id,
                'tid' => $transaction->tid,
                'amount' => $transaction->amount,
                'amount_output' => priceOutput($transaction->amount),
                'credit' => $transaction->credit,

                'title' => $title,

                'time_display' => parseTime($transaction->time, $time_zone, 'dateTime'),

            ];
        }

        // return all the goodies
        return view('pages.wallet.transactions',
            [
                'page' => 'transactions',
                'menu_links' => $this->getMenuLinks(),
                'db_transactions' => $db_transactions,
                'user_transactions' => $user_transactions
            ]
        );
    }

    public function viewItemPurchases ()
    {
        $user_id = Auth::id();
        $time_zone = Auth::user()->getTimeZone();

        $db_purchases =
            DB::table('item_purchases AS ip')
                ->where('ip.user_id', $user_id)
                ->join('item_sales AS is', 'ip.sale_id', '=', 'is.id')
                ->leftJoin('trade_offers AS to', 'ip.trade_id', '=', 'to.id')
                ->orderBy('ip.id', 'desc')
                ->select('ip.trade_id', 'ip.time', 'is.id AS sale_id', 'is.name', 'is.exterior', 'is.price', 'to.status AS delivery_status')
                ->paginate(20);

        $user_purchases = [];

        foreach ($db_purchases as $purchase) {

            if (!isset($purchase->delivery_status)) {
                $purchase->delivery_status = null;
            }

            $user_purchases[$purchase->sale_id] = [
                'name' => $purchase->name,
                'exterior' => $purchase->exterior,
                'price' => $purchase->price,
                'price_output' => priceOutput($purchase->price),
                'trade_id' => $purchase->trade_id,
                'delivery_status' => $purchase->delivery_status,
                'time' => $purchase->time,
                'time_display' => parseTime($purchase->time, $time_zone, 'dateTime')
            ];
        }

        // return all the goodies
        return view('pages.wallet.item_purchases',
            [
                'page' => 'item_purchases',
                'menu_links' => $this->getMenuLinks(),
                'db_purchases' => $db_purchases,
                'user_purchases' => $user_purchases
            ]
        );
    }

    public function getMenuLinks ()
    {
        return [
            ['page' => '', 'name' => 'Wallet'],
            ['page' => 'transactions', 'name' => 'Transactions'],
            ['page' => 'item_purchases', 'name' => 'Item Purchases']
        ];
    }

}