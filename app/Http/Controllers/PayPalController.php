<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\App;

use PayPal\Api\Payer;
use PayPal\Api\Amount;
use PayPal\Api\PaymentOptions;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Api\Payment;
use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\PaymentExecution;
use PayPal\Api\OpenIdTokeninfo;
use PayPal\Api\OpenIdUserinfo;

use DateTime;

class PayPalController extends Controller
{
    private $apiContext;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('throttle:30,15');

        $this->apiContext = new ApiContext( new OAuthTokenCredential( config('app.paypal_client_id'), config('app.paypal_client_secret')));

        if (App::environment('production')) {

            $paypal_mode = 'live';
            $this->apiContext = new ApiContext( new OAuthTokenCredential( config('app.paypal_client_id'), config('app.paypal_client_secret')));

        } else {

            $paypal_mode = 'sandbox';
            $this->apiContext = new ApiContext( new OAuthTokenCredential( config('app.paypal_sandbox_client_id'), config('app.paypal_sandbox_client_secret')));
        }

        $this->apiContext->setConfig(
            [
                'mode' => $paypal_mode,
                'log.LogEnabled' => true,
                'log.FileName' => '/var/www/html/storage/logs/PayPal.log',
                'log.LogLevel' => 'INFO', // USE `INFO` LEVEL FOR LOGGING IN LIVE ENVIRONMENTS
                'cache.enabled' => false,
            ]
        );
    }

    public function payment (Request $request)
    {
        return response('', 401);

        $this->validate($request, [
            'amount' => 'required|numeric',
        ]);

        $user_id = Auth::id();
        $id_verified = Auth::user()->getIdVerified();
        $paypal_linked = DB::table('paypal_accounts')->where(['user_id' => $user_id])->value('id');

        if ($id_verified != 1 || $paypal_linked == null) {
            return response('', 401);
        }

        $amount = number_format($request->input('amount'), 2, '.', '');

        // check for max and min payment limits
        if ($amount >= config('app.min_fund_paypal') && $amount <= config('app.max_fund_paypal')) {

            $purchase_limit_check = $this->checkPayPalPurchaseLimits($amount);

            if ($purchase_limit_check['error'] != '') {
                return response()->json([
                    'status' => 0,
                    'error' => $purchase_limit_check['error']
                ]);
            }

            try {

                $payer = new Payer();
                $payer->setPaymentMethod("paypal");

                $paypal_amount = new Amount();
                $paypal_amount->setCurrency("USD")->setTotal($amount);

                $payment_order_id =
                    DB::table('payment_orders')
                        ->insertGetId(
                            [
                                'user_id' => $user_id,
                                'source' => config('app.paypal_payments_id'),
                                'amount' => $amount,
                                'time' => time()
                            ]
                        );

                $payment_options = new PaymentOptions();
                $payment_options->setAllowedPaymentMethod('IMMEDIATE_PAY');

                $transaction = new Transaction();
                $transaction->setAmount($paypal_amount)
                            ->setDescription('User ID ('.$user_id.') -- Funds (' . priceOutput($amount) . ')')
                            ->setCustom($user_id)
                            ->setInvoiceNumber($payment_order_id)
                            ->setPaymentOptions($payment_options);

                $redirectUrls = new RedirectUrls();
                $redirectUrls->setReturnUrl(config('app.url')."/wallet?paypal_payment=1")
                             ->setCancelUrl(config('app.url')."/wallet?paypal_payment=0");

                $payment = new Payment();
                $payment->setIntent("sale")
                        ->setPayer($payer)
                        ->setRedirectUrls($redirectUrls)
                        ->setTransactions([$transaction]);

                $payment->create($this->apiContext);

            } catch (\Exception $e) {

                DB::rollBack();

                return response()->json([
                    'status' => 0,
                    'error' => 'Error while creating payment.'
                ]);
            }

            DB::commit();

            $redirect_url = $payment->getApprovalLink();

            return response()->json([
                'status' => 1,
                'url' => $redirect_url
            ]);

        } else {

            return response()->json([
                'status' => 0,
                'error' => 'Invalid amount'
            ]);
        }

    }

    public function paymentCapture ($request)
    {
        $user_id = Auth::id();

        if ($request->get('paypal_payment') == 1) {

            try {

                $payment = Payment::get($request->input('paymentId'), $this->apiContext);

                // get id verification details
                $id_verification = DB::table('id_verification')->where('user_id', $user_id)->select('full_name', 'country')->first();

                // set PayPal return info
                $paypal_verified_status = $payment->payer->getStatus();
                $paypal_payer_info = $payment->payer->getPayerInfo();
                $payment_payer_id = $paypal_payer_info->getPayerId();
                $paypal_first_name = $paypal_payer_info->getFirstName();
                $paypal_middle_name = $paypal_payer_info->getMiddleName();
                $paypal_last_name = $paypal_payer_info->getLastName();
                $paypal_country = $paypal_payer_info->getCountryCode();

                $custom_field_user_id = $payment->transactions[0]->getCustom();
                $credit_amount = $payment->transactions[0]->getAmount()->getTotal();

                // combine PayPal first, middle, and last name so we can compare with ID verification full name
                $paypal_name = $paypal_first_name .' '. $paypal_middle_name .' '. $paypal_last_name;

                // we'll do a similarity check for id verification name and PayPal name
                // because the names might not be exactly the same
                // if it matches 90% or more, it should be fine
                $percent_threshold = 90;
                similar_text($id_verification->full_name, $paypal_name, $name_percent_match);

                $user_payer_id = DB::table('paypal_accounts')->where('user_id', $user_id)->value('payer_id');

                // Custom field user_id must match logged in user_id
                // Connected PayPal payer_id must match Payment payer_id
                // PayPal account must be VERIFIED
                // ID verification details must match PayPal details
                if (
                    ($user_id != $custom_field_user_id)
                    || ($user_payer_id != $payment_payer_id)
                    || ($paypal_verified_status != 'VERIFIED')
                    || ($id_verification->country != $paypal_country)
                    || ($name_percent_match <= $percent_threshold)
                ) {

                    return redirect()
                        ->route('wallet')
                        ->with('alert',
                            '<i class="icon-x"></i> You were not charged. You must pay with your connected PayPal account.
                            <br>Contact support to disconnect your current PayPal account.');
                }

                $execution = new PaymentExecution();
                $execution->setPayerId($request->input('PayerID'));

                $executeResult = $payment->execute($execution, $this->apiContext);

                if ($executeResult->state == 'approved') {

                    // all good, credit funds to user

                    DB::beginTransaction();

                    try {

                        // insert new transaction
                        $transaction_id =
                            DB::table('transactions')
                            ->insertGetId(
                                [
                                    'user_id' => $user_id,
                                    'tid' => config('app.paypal_funds_tid'),
                                    'amount' => $credit_amount,
                                    'credit' => 1,
                                    'time' => time()
                                ]
                            );

                        // update buyer wallet to reflect purchase
                        Auth::user()->updateBalanceForAddedFunds($credit_amount);

                        Log::notice(
                            'PayPal (captured)',
                            [
                                'user_id' => $user_id,
                                'credit_amount' => $credit_amount,
                                'transaction_id' => $transaction_id,
                                'time' => time(),
                            ]
                        );

                        DB::commit();

                        return redirect()
                            ->route('wallet')
                            ->with('alert',
                                    '<i class="icon-checkmark"></i> Purchase complete. Thank you.');

                    } catch (\Exception $e) {

                        // we captured PayPal payment but failed when giving user the funds

                        DB::rollBack();

                        Log::alert(
                            'PayPal (FAILED TO CREDIT USER WALLET)',
                            [
                                'user_id' => $user_id,
                                'credit_amount' => $credit_amount,
                                'time' => time()
                            ]
                        );

                        return redirect()
                            ->route('wallet')
                            ->with('alert',
                                '<i class="icon-x"></i> PLEASE CONTACT US IMMEDIATELY.<br>
                                We charged your PayPal account but failed to credit your wallet.');
                    }

                } else {

                    return redirect()->route('wallet')->with('alert', 'Something went wrong, you were not charged.');
                }

            } catch (\Exception $e) {

                return redirect()->route('wallet')->with('alert', 'Something went wrong, please try again.');
            }

        }

        return redirect()->route('wallet')->with('alert', 'You were not charged.');
    }

    public function connectPayPal($request)
    {
        $user_id = Auth::id();
        $id_verified = Auth::user()->getIdVerified();
        $auth_code = $request->input('code');

        $paypal_linked = DB::table('paypal_accounts')->where(['user_id' => $user_id])->value('id');

        if ($id_verified != 1 || $paypal_linked != null) {
            return false;
        }

        try {

            // get id verification details
            $id_verification = DB::table('id_verification')->where('user_id', $user_id)->select('full_name', 'country')->first();

            // set PayPal token info
            $tokenInfo = new OpenIdTokeninfo();
            $tokenInfo = $tokenInfo->createFromAuthorizationCode(['code' => $auth_code], null, null, $this->apiContext);

            $params = ['access_token' => $tokenInfo->getAccessToken()];
            $userInfo = OpenIdUserinfo::getUserinfo($params, $this->apiContext);

            $paypal_account_verified = $userInfo->getVerifiedAccount();

            // PayPal account must be verified
            if (!$paypal_account_verified) {
                return redirect()
                    ->route('wallet.add_funds')
                    ->with('alert',
                        'Your PayPal account must be 
                        <a href="https://www.paypal.com/us/selfhelp/article/How-do-I-get-Verified-FAQ444" target="_blank">verified</a>.');
            }

            // restriction: account must be older than 1 months
            if (Auth::user()->getPayPalRestriction1() == 1) {

                $account_creation_date = $userInfo->account_creation_date;

                $dateTime = new DateTime($account_creation_date);
                $account_creation_unix = $dateTime->format('U');

                if ($account_creation_unix > strtotime('-1 month')) {
                    return redirect()->route('wallet.add_funds')
                        ->with(
                            'alert',
                            'Your PayPal account must be at least 1 month old.
                            <br>You created your PayPal account on ' . parseTime($account_creation_unix, 'UTC', 'date') . '.');
                }
            }

            $paypal_user_id = $userInfo->getUserId();
            // remove stupid prefix for user_id
            $paypal_user_id = str_replace('https://www.paypal.com/webapps/auth/identity/user/', '', $paypal_user_id);

            $paypal_payer_id = $userInfo->getPayerId();

            // no payer id in sandbox
            if ($paypal_payer_id == null && App::environment('local')) {
                $paypal_payer_id = time();
            }

            $paypal_name = $userInfo->getName();

            $paypal_address = $userInfo->getAddress();
            $paypal_country = $paypal_address->getCountry();

            // PayPal address can't be blank
            if (empty($paypal_country)) {

                return
                    redirect()
                        ->route('wallet.add_funds')
                        ->with('alert', 'We could not connect your PayPal account, please contact support.');
            }

            // check if this PayPal account already connected to another Marketplace account
            $duplicate_check = DB::table('paypal_accounts')->where('payer_id', $paypal_payer_id)->value('id');

            if ($duplicate_check != null || empty($paypal_payer_id)) {
                return redirect()->route('wallet.add_funds')->with('alert',
                        'Sorry, this PayPal account is already connected to another Marketplace account.<br>Contact us to disconnect your PayPal account.');
            }

            // we'll do a similarity check for id verification name and PayPal name
            // because the names might not be exactly the same
            // if it matches 90% or more, it should be fine
            $percent_threshold = 90;
            similar_text($id_verification->full_name, $paypal_name, $name_percent_match);

            // id verification details must match PayPal details
            if ($id_verification->country != $paypal_country || ($name_percent_match <= $percent_threshold)) {

                return redirect()->route('wallet.add_funds')
                    ->with('alert', '<i class="icon-x"></i> Your PayPal name or country does not match your ID verification details.');
            }

            // all is good, save user paypal details and commit
            DB::table('paypal_accounts')
                ->insert([
                    'user_id' => $user_id,
                    'paypal_user_id' => $paypal_user_id,
                    'payer_id' => $paypal_payer_id
                ]);

            DB::commit();

            session(['alert' => '<i class="icon-checkmark"></i> You\'ve connected your PayPal account, you can now add funds.']);

        } catch (\Exception $e) {

            // error connecting PayPal account

            DB::rollBack();

            session(['alert' => 'Failed to connect PayPal account. Please try again.']);
        }

    }

    public function checkPayPalPurchaseLimits($amount)
    {
        $user_id = Auth::id();
        $strtotime_1d = strtotime('-1 day');
        $strtotime_30d = strtotime('-30 day');

        // enforce DAILY spending limit PER USER
        $user_amount_spent_today =
            DB::table('transactions')
                ->where(['user_id' => $user_id, 'tid' => config('app.paypal_funds_tid')])
                ->where('time', '>', $strtotime_1d)
                ->sum('amount');

        if (($user_amount_spent_today + $amount) > config('app.paypal_24hr_funds_limit')) {
            return ['error' =>
                    'Max amount: '.priceOutput( max(config('app.paypal_24hr_funds_limit') - ($user_amount_spent_today), 0)).'
                    <br>Amount entered would exceed your <b>24-hour</b> PayPal purchase limit ('.priceOutput(config('app.paypal_24hr_funds_limit')).').'];
        }

        // enforce 30 DAY spending limit PER USER
        $user_amount_spent_30d =
            DB::table('transactions')
                ->where(['user_id' => $user_id, 'tid' => config('app.paypal_funds_tid')])
                ->where('time', '>', $strtotime_30d)
                ->sum('amount');

        if (($user_amount_spent_30d + $amount) > config('app.paypal_30d_funds_limit')) {
            return ['error' =>
                    'Max amount: '.priceOutput( max(config('app.paypal_30d_funds_limit') - ($user_amount_spent_30d), 0)).'
                    <br>Amount entered would exceed your <b>30-day</b> PayPal purchase limit ('.priceOutput(config('app.paypal_30d_funds_limit')).').'];
        }

        return [
            'error' => ''
        ];
    }

}
