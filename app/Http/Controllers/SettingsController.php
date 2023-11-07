<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('throttle:15,1')->only('ajax');
    }

    public function index ()
    {
        $user_id = Auth::id();
        $email = Auth::user()->getEmail();
        $trade_url = Auth::user()->getTradeUrl();
        $time_zone = Auth::user()->getTimeZone();
        $show_wallet_amount = Auth::user()->getShowWalletAmount();
        $send_sales_receipts = Auth::user()->getSendSalesReceipts();
        $send_purchase_receipts = Auth::user()->getSendPurchaseReceipts();

        if (!empty($trade_url)) {
            $trade_url = config('app.trade_url_prefix').$trade_url;
        }

        // handle time zone options display

        $time_zones = getTimeZones();
        $time_zone_options = '';

        foreach ($time_zones as $time_zone_name => $value) {

            if ($time_zone != $value)
                $time_zone_options .= '<option value="'.$value.'">'.$time_zone_name.'</option>';

            elseif ($time_zone == $value)
                $time_zone_options .= '<option value="'.$value.'" selected="">'.$time_zone_name.'</option>';
        }

        // get referrals
        $referrals = DB::table('referrals')->where('referral_user_id', $user_id);

        $referrals_sum = "$0.00";
        $referrals_count = 0;

        if ($referrals != null) {
            $referrals_sum = priceOutput($referrals->sum('sale_commission_amount'));
            $referrals_count = $referrals->count();
        }

        // return all the goodies

        return view('pages.settings.index',
            [
                'email' => $email,
                'trade_url' => $trade_url,
                'time_zone_options' => $time_zone_options,
                'show_wallet_amount' => $show_wallet_amount,
                'send_sales_receipts' => $send_sales_receipts,
                'send_purchase_receipts' => $send_purchase_receipts,
                'referrals_sum' => $referrals_sum,
                'referrals_count' => $referrals_count
            ]
        );
    }

    public function ajax (Request $request) {

        /*** Change Email ***/

        if ($request->input('email')) {

            $this->validate($request, [
                'email' => 'required|email',
            ]);

            $post_email = $request->input('email');

            Auth::user()->email = $post_email;
            Auth::user()->save();

            return response('ok');
        }

        /*** Change Trade URL ***/

        elseif ($request->input('trade_url')) {

            $this->validate($request, [
                'trade_url' => 'required|url',
            ]);

            $trade_url = $request->input('trade_url');

            $steam_id = Auth::user()->getSteamId();

            if (validTradeUrl($trade_url, $steam_id)) {

                Auth::user()->trade_url = trimTradeUrl($trade_url);
                Auth::user()->save();

                return response('ok');
            }

            return response('Not valid');
        }

        /*** Change Time Zone ***/

        elseif ($request->input('time_zone')) {

            $time_zone = $request->input('time_zone');

            $time_zones = getTimeZones();

            if (in_array($time_zone, $time_zones)) {

                Auth::user()->time_zone = $time_zone;
                Auth::user()->save();

                return response('ok');
            }
        }

        /*** Change Show Wallet Amount ***/

        elseif ($request->input('show_wallet_amount')) {

            $value = $request->input('show_wallet_amount');

            $value = $value == 'true' ? 1 : 0;

            Auth::user()->show_wallet_amount = $value;
            Auth::user()->save();

            return response('ok');
        }

        /*** Change Send Sales Receipts ***/

        elseif ($request->input('send_sales_receipts')) {

            $value = $request->input('send_sales_receipts');

            $value = $value == 'true' ? 1 : 0;

            Auth::user()->send_sales_receipts = $value;
            Auth::user()->save();

            return response('ok');
        }

        /*** Change Send Purchase Receipts ***/

        elseif ($request->input('send_purchase_receipts')) {

            $value = $request->input('send_purchase_receipts');

            $value = $value == 'true' ? 1 : 0;

            Auth::user()->send_purchase_receipts = $value;
            Auth::user()->save();

            return response('ok');
        }

        return response('', 401);
    }

}