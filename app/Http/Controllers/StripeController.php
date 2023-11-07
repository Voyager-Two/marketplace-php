<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

use Stripe\Stripe;

class StripeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('throttle:30,15');
    }

    public function payment (Request $request)
    {
        return response()->json([
            'status' => 0,
            'error' => 'Stripe payments disabled temporarily.'
        ]);

        $this->validate($request, [
            'amount' => 'required|numeric',
        ]);

        $user_id = Auth::id();
        $user_country = Auth::user()->getCountry();

        $this->validate($request, [
            'token_id' => 'required|alpha_dash'
        ]);

        $stripe_token_id = $request->input('token_id');
        $stripe_amount = $request->input('amount');
        $amount = $stripe_amount / 100;

        // the amount charged to their card has a surcharge, calculate out the surcharge
        $credit_amount = number_format((($amount - 0.30) * (1 - 0.029) - 0.01), 2, '.', '');

        // check for max and min payment limits
        if ($credit_amount >= config('app.min_fund_card') && $credit_amount <= config('app.max_fund_card')) {

            Stripe::setApiKey(config('app.stripe_api_key'));

            $stripe_transaction = \Stripe\Token::retrieve($stripe_token_id);

            $_card = $stripe_transaction->card;

            if (
                ($_card->country != $user_country) || ($_card->cvc_check == null) ||
                ($_card->address_city == null) || ($_card->address_line1 == null) || ($_card->address_zip == null) ||
                ($_card->cvc_check != 'pass') || ($_card->address_zip_check != 'pass') || ($_card->address_line1_check != 'pass')
            )
            {
                return response()->json([
                    'status' => 0,
                    'error' => 'You were not charged.<br>Payment verification failed.<br>Please make sure you have entered the correct personal information.'
                ]);
            }

            $card_fingerprint = $stripe_transaction->card->fingerprint;

            // check if card fingerprint or address belongs to any suspended users
            $stripe_matches =
                DB::table('stripe_transactions')
                    ->where(
                        ['card_fingerprint' => $card_fingerprint],
                        ['user_id', '!=', $user_id]
                    )
                    ->orWhere(
                        [
                            'address_line1' => $_card->address_line1,
                            'address_zip' => $_card->address_zip,
                            ['user_id', '!=', $user_id],
                        ]
                    )
                    ->select('user_id')->get();

            if ($stripe_matches != null) {

                foreach ($stripe_matches as $stripe_match) {

                    $is_user_suspended = DB::table('suspensions')->where('user_id', $stripe_match->user_id)->value('id');

                    if ($is_user_suspended != null) {

                        // card or address belongs to suspended user

                        return response()->json([
                            'status' => 0,
                            'error' => 'You were not charged.<br>Card authorization failed, please contact support for details.'
                        ]);
                    }
                }
            }

            $purchase_limit_check = $this->checkPurchaseLimits($credit_amount,$card_fingerprint);

            if ($purchase_limit_check['error'] != '') {
                return response()->json([
                    'status' => 0,
                    'error' => $purchase_limit_check['error']
                ]);
            }

            $charge =
                \Stripe\Charge::create(
                    [
                        'amount' => $stripe_amount,
                        'currency' => 'usd',
                        'description' => 'User ID ('.$user_id.') -- Funds (' . priceOutput($credit_amount) . ')',
                        'source' => $stripe_token_id,
                    ]
                );

            if ($charge->captured) {

                // all good, add funds to user

                DB::beginTransaction();

                try {

                    // insert new transaction
                    DB::table('transactions')
                        ->insert(
                            [
                                'user_id' => $user_id,
                                'tid' => config('app.card_funds_tid'),
                                'amount' => $credit_amount,
                                'credit' => 1,
                                'time' => time()
                            ]
                        );

                    // insert new stripe transaction
                    DB::table('stripe_transactions')
                        ->insert(
                            [
                                'user_id' => $user_id,
                                'card_fingerprint' => $card_fingerprint,
                                'amount' => $credit_amount,
                                'address_zip' => $_card->address_zip,
                                'address_line1' => $_card->address_line1,
                                'time' => time()
                            ]
                        );

                    // update buyer wallet to reflect purchase
                    Auth::user()->updateBalanceForAddedFunds($credit_amount);

                    Log::notice(
                        'Stripe (captured)',
                        [
                            'user_id' => $user_id,
                            'credit_amount' => $credit_amount,
                            'stripe_token_id' => $stripe_token_id,
                            'time' => time(),
                        ]
                    );

                } catch (\Exception $e) {

                    // we charged the card but failed when giving user the funds

                    DB::rollBack();

                    Log::alert(
                        'Stripe (FAILED TO CREDIT USER WALLET)',
                        [
                            'user_id' => $user_id,
                            'credit_amount' => $credit_amount,
                            'stripe_token_id' => $stripe_token_id,
                            'time' => time()
                        ]
                    );

                    return response()->json([
                        'status' => 0,
                        'error' => 'PLEASE CONTACT US IMMEDIATELY.<br>We charged your card but failed to credit your wallet.<br>Reference: ' . $stripe_token_id
                    ]);
                }

                DB::commit();

                // user will see this message when redirected to wallet page
                session(['alert' => '<i class="icon-checkmark"></i> Purchase complete. Thank you.']);

                return response()->json([
                    'status' => 1
                ]);

            } else {

                return response()->json([
                    'status' => 0,
                    'error' => $charge->failure_message,
                ]);
            }

        } else {

            return response()->json([
                'status' => 0,
                'error' => 'Invalid amount.',
            ]);
        }

    }

    public function checkPurchaseLimits($amount, $card_fingerprint='', $address_zip='', $address_line1='')
    {
        return response()->json([
            'error' => 'Stripe payments disabled temporarily.'
        ]);

        $user_id = Auth::id();
        $strtotime_1d = strtotime('-1 day');
        $strtotime_30d = strtotime('-30 day');

        // enforce DAILY spending limit PER USER
        $user_transactions_today =
            DB::table('stripe_transactions')
                ->where('user_id', $user_id)
                ->where('time', '>', $strtotime_1d)
                ->select('amount')->get();

        if ($user_transactions_today->count() >= config('app.card_24hr_count_limit')) {
            return ['error' => 'You are limited to ' . config('app.card_24hr_count_limit') . ' card transactions per day.'];
        }

        $user_amount_spent_today = 0;

        foreach ($user_transactions_today as $user_transaction) {
            $user_amount_spent_today += $user_transaction->amount;
        }

        if (($user_amount_spent_today + $amount) > config('app.card_24hr_funds_limit')) {
            return ['error' =>
                'Max amount: ' . priceOutput(max(config('app.card_24hr_funds_limit') - ($user_amount_spent_today), 0)) . '
                <br>Amount entered would exceed your <b>24-hour</b> card purchase limit (' . priceOutput(config('app.card_24hr_funds_limit')) . ').'];
        }

        // enforce 30 DAY spending limit PER USER
        $user_transactions_30d =
            DB::table('stripe_transactions')
                ->where('user_id', $user_id)
                ->where('time', '>', $strtotime_30d)
                ->select('amount')->get();

        if ($user_transactions_30d->count() >= config('app.card_30d_count_limit')) {
            return ['error' => 'You are limited to ' . config('app.card_30d_count_limit') . ' card transactions per month.'];
        }

        $user_amount_spent_30d = 0;

        foreach ($user_transactions_30d as $user_30d_transaction) {
            $user_amount_spent_30d += $user_30d_transaction->amount;
        }

        if (($user_amount_spent_30d + $amount) > config('app.card_30d_funds_limit')) {
            return ['error' =>
                'Max amount: ' . priceOutput(max(config('app.card_30d_funds_limit') - ($user_amount_spent_30d), 0)) . '
                <br>Amount entered would exceed your <b>30-day</b> card purchase limit (' . priceOutput(config('app.card_30d_funds_limit')) . ').'];
        }

        if ($card_fingerprint != '') {

            // we need to enforce account limits to individual cards and addresses
            // this is also prevents persons from exceeding card limits with a different account

            // enforce DAILY spending limit PER CARD or Address
            $card_transactions_today =
                DB::table('stripe_transactions')
                    ->where(
                        ['address_line1' => $address_line1, 'address_zip' => $address_zip],
                        ['time', '>', $strtotime_1d]
                    )
                    ->select('amount')->get();

            if ($card_transactions_today->count() >= config('app.card_24hr_count_limit')) {
                return ['error' => 'You are limited to '.config('app.card_24hr_count_limit').' card transactions per day / per household.'];
            }

            $card_amount_spent_today = 0;

            foreach ($card_transactions_today as $card_transaction) {
                $card_amount_spent_today += $card_transaction->amount;
            }

            if (($card_amount_spent_today + $amount) > config('app.card_24hr_funds_limit')) {
                return ['error' =>
                        'Max amount: '.priceOutput( max(config('app.card_24hr_funds_limit') - ($card_amount_spent_today), 0)).'
                        <br>Amount entered would exceed your <b>24-hour</b> card purchase limit ('.priceOutput(config('app.card_24hr_funds_limit')).').'];
            }

            // enforce 30 DAY spending limit PER CARD or Address
            $card_transactions_30d =
                DB::table('stripe_transactions')
                    ->where(
                        ['address_line1' => $address_line1, 'address_zip' => $address_zip],
                        ['time', '>', $strtotime_30d]
                    )
                    ->select('amount')->get();

            if ($card_transactions_30d->count() >= config('app.card_30d_count_limit')) {
                return ['error' => 'You are limited to '.config('app.card_30d_count_limit').' card transactions per day / per household.'];
            }

            $card_amount_spent_30d = 0;

            foreach ($card_transactions_30d as $card_transaction_30d) {
                $card_amount_spent_30d += $card_transaction_30d->amount;
            }

            if (($card_amount_spent_30d + $amount) > config('app.card_30d_funds_limit')) {
                return ['error' =>
                        'Max amount: '.priceOutput( max(config('app.card_30d_funds_limit') - ($card_amount_spent_30d), 0)).'
                        <br>Amount entered would exceed your <b>30-day</b> card purchase limit ('.priceOutput(config('app.card_30d_funds_limit')).').'];
            }
        }

        return [
            'error' => ''
        ];
    }

}
