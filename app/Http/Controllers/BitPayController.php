<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\WalletBalance;
use Illuminate\Support\Facades\Auth;

use Coinbase\Wallet\Client;
use Coinbase\Wallet\Configuration;
use Coinbase\Wallet\Resource\Checkout;
use Coinbase\Wallet\Value\Money;

class BitPayController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('throttle:30,15')->except('bitpayIPN');
    }

    public function coinbasePayment(Request $request)
    {
        $this->validate($request, [
            'amount' => 'required|numeric',
        ]);

        $user_id = Auth::id();

        return response('', 401);

        $amount = number_format($request->input('amount'), 2, '.', '');

        // check for max and min payment limits
        if ($amount >= config('app.min_fund_bitcoin') && $amount <= config('app.max_fund_bitcoin')) {

            try {

                $user_email = Auth::user()->getEmail();

                $checkout = new Checkout($params);

                $configuration = Configuration::apiKey(config('app.coinbase_api_key'), config('app.coinbase_api_secret'));
                $client = Client::create($configuration);

                $payment_order_id =
                    DB::table('payment_orders')
                        ->insertGetId(
                            [
                                'user_id' => $user_id,
                                'source' => config('app.coinbase_payments_id'),
                                'amount' => $amount,
                                'time' => time()
                            ]
                        );

                $params = [
                    'name' => 'User ID ('.$user_id.') -- Funds (' . priceOutput($amount) . ')',
                    'amount' => new Money($amount, 'USD'),
                    'metadata' => ['order_id' => $payment_order_id]
                ];

                $client->createCheckout($checkout);
                $code = $checkout->getEmbedCode();

            } catch (\Exception $e) {

                return response()->json([
                    'status' => 0,
                    'error' => 'Error while creating checkout.'
                ]);
            }

            $redirect_url = "https://www.coinbase.com/checkouts/$code";

            // set the message user will see when we redirect them to wallet after payment
            session(['alert' => '<i class="icon-checkmark"></i> You will be credited once your Bitcoin payment is confirmed (10 to 30 minutes).<br>Thank you for your patience.']);

            return response()->json([
                'status' => 1,
                'id' => $redirect_url
            ]);

        } else {
            return response()->json([
                'status' => 0,
                'error' => 'Invalid amount'
            ]);
        }

        return response('', 401);
    }

    public function payment(Request $request)
    {

        return response('', 401);

        $this->validate($request, [
            'amount' => 'required|numeric',
        ]);

        $user_id = Auth::id();

        // Bitcoin via BitPay

        $amount = number_format($request->input('amount'), 2, '.', '');

        // check for max and min payment limits
        if ($amount >= config('app.min_fund_bitcoin') && $amount <= config('app.max_fund_bitcoin')) {

            $user_email = Auth::user()->getEmail();

            $storageEngine = new \Bitpay\Storage\EncryptedFilesystemStorage(config('app.key'));
            $privateKey = $storageEngine->load('/etc/nginx/ssl/bitpay.pri');
            $publicKey = $storageEngine->load('/etc/nginx/ssl/bitpay.pub');
            $client = new \Bitpay\Client\Client();
            //$network = new \Bitpay\Network\Testnet();
            $network = new \Bitpay\Network\Livenet();
            $adapter = new \Bitpay\Client\Adapter\CurlAdapter();
            $client->setPrivateKey($privateKey);
            $client->setPublicKey($publicKey);
            $client->setNetwork($network);
            $client->setAdapter($adapter);

            $token = new \Bitpay\Token();
            $token->setToken(config('app.bitpay_api_key'));

            $client->setToken($token);

            $invoice = new \Bitpay\Invoice();

            $buyer = new \Bitpay\Buyer();
            $buyer->setEmail($user_email);

            $invoice->setBuyer($buyer);

            $invoice->setFullNotifications(true);

            $item = new \Bitpay\Item();

            $item
                ->setCode('Add Funds (' . priceOutput($amount) . ')')
                ->setDescription('Add Funds (' . priceOutput($amount) . ')')
                ->setPrice($amount);

            $invoice->setItem($item);

            $invoice->setCurrency(new \Bitpay\Currency('USD'));

            $bitpay_order_id =
                DB::table('payment_orders')
                ->insertGetId(
                    [
                        'user_id' => $user_id,
                        'source' => config('app.bitpay_payments_id'),
                        'amount' => $amount,
                        'time' => time()
                    ]
                );

            $invoice
                ->setOrderId($bitpay_order_id)
                ->setNotificationUrl('https://Marketplace.io/payments/bitpay_ipn');

            try {

                $client->createInvoice($invoice);

            } catch (\Exception $e) {

                return response()->json([
                    'status' => 0,
                    'error' => 'Error while creating invoice'
                ]);
            }

            // set the message user will see when we redirect them to wallet after payment
            session(['alert' => 'Bitcoin payments take 10 to 30 minutes to confirm after payment.']);

            return response()->json([
                'status' => 1,
                'id' => $invoice->getId(),
            ]);

        } else {
            return response()->json([
                'status' => 0,
                'error' => 'Invalid amount'
            ]);
        }

    }

    public function IPN(Request $request, WalletBalance $walletBalance)
    {
        if (!$request->input('id')) {
            return response('', 400);
        }

        // Fetch the invoice from BitPay
        // This is needed, since the IPN does not contain any authentication
        $client = new \Bitpay\Client\Client();
        //$network = new \Bitpay\Network\Testnet();
        $network = new \Bitpay\Network\Livenet();
        $adapter = new \Bitpay\Client\Adapter\CurlAdapter();
        $client->setNetwork($network);
        $client->setAdapter($adapter);
        $token = new \Bitpay\Token();
        $client->setToken($token);

        $invoice = $client->getInvoice($request->input('id'));
        $invoiceId = $invoice->getId();
        $invoiceStatus = $invoice->getStatus();
        $invoiceExceptionStatus = $invoice->getExceptionStatus();
        $invoicePrice = $invoice->getPrice();
        $invoiceOrderId = $invoice->getOrderId();

        // payment was confirmed based on our bitpay setting of 'high, med, or low' risk
        if ($invoiceStatus == 'confirmed' && $this->validInvoice($invoiceExceptionStatus)) {

            /*
            | it's okay if it is 'over paid' or 'paid late' because we will only credit the amount in USD they chose to add
            | that amount is already saved in our bitpay_orders table
            */

            // payment confirmed, credit the user

            DB::beginTransaction();

            try {

                $user_data = DB::table('payment_orders')->where('id', $invoiceOrderId)->select('user_id', 'amount')->first();
                $user_id = $user_data->user_id;
                $credit_amount = $user_data->amount;

                // update bitpay_orders status
                DB::table('payment_orders')->where('id', $invoiceOrderId)->update(['status' => config('app.bitpay_status_confirmed')]);

                // insert new transaction
                DB::table('transactions')
                    ->insert(
                        [
                            'user_id' => $user_id,
                            'tid' => config('app.bitcoin_funds_tid'),
                            'amount' => $credit_amount,
                            'credit' => 1,
                            'time' => time()
                        ]
                    );

                // update buyer wallet to reflect purchase
                $walletBalance->updateBalanceForAddedFunds($user_id, $credit_amount);

                Log::notice(
                    'BitPay IPN (confirmed)',
                    [
                        'id' => $invoiceId,
                        'order_id' => $invoiceOrderId,
                        'status' => $invoiceStatus,
                        'ex_status' => $invoiceExceptionStatus,
                        'user_id' => $user_id,
                        'amount' => $credit_amount,
                        'time' => time()
                    ]
                );

            } catch (\Exception $e) {

                // we failed to credit user wallet!

                DB::rollBack();

                $user_id = DB::table('payment_orders')->where('id', $invoiceOrderId)->value('user_id');

                Log::alert(
                    'BitPay IPN (ATTN: FAILED TO CREDIT USER WALLET)',
                    [
                        'id' => $invoiceId,
                        'order_id' => $invoiceOrderId,
                        'status' => $invoiceStatus,
                        'ex_status' => $invoiceExceptionStatus,
                        'user_id' => $user_id,
                        'amount' => $invoicePrice,
                        'time' => time()
                    ]
                );

                return response('', 400);
            }

            DB::commit();

            // Respond with HTTP 200, so BitPay knows the IPN has been received and processed
            // If BitPay receives anything other than HTTP 200, then BitPay will try to send the IPN again with increasing time intervals
            return response('', 200);

        } elseif ($invoiceStatus == 'paid' && $this->validInvoice($invoiceExceptionStatus)) {

            // payment was 'paid', it still needs to be confirmed
            // change the status in our bitpay_orders table

            $user_id = DB::table('payment_orders')->where('id', $invoiceOrderId)->value('user_id');

            Log::alert(
                'BitPay IPN (paid)',
                [
                    'id' => $invoiceId,
                    'order_id' => $invoiceOrderId,
                    'status' => $invoiceStatus,
                    'ex_status' => $invoiceExceptionStatus,
                    'user_id' => $user_id,
                    'amount' => $invoicePrice,
                    'time' => time()
                ]
            );

            DB::table('payment_orders')->where('id', $invoiceOrderId)->update(['status' => config('app.bitpay_status_paid')]);

            return response('', 200);

        } else {

            // other payment status changes

            $user_id = DB::table('payment_orders')->where('id', $invoiceOrderId)->value('user_id');

            Log::notice(
                'BitPay IPN (else)',
                [
                    'id' => $invoiceId,
                    'order_id' => $invoiceOrderId,
                    'status' => $invoiceStatus,
                    'ex_status' => $invoiceExceptionStatus,
                    'user_id' => $user_id,
                    'amount' => $invoicePrice,
                    'time' => time()
                ]
            );

            return response('', 200);
        }
    }

    public function validInvoice($invoice_exception)
    {
        $valid_invoice_exceptions = 'paidOver';

        // false or paidOver are all valid
        if ($invoice_exception == false || strpos($valid_invoice_exceptions, $invoice_exception) !== false) {
            return true;
        }

        return false;
    }
}
