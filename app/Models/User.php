<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class User extends Model implements AuthenticatableContract
{
    use Authenticatable;

    protected $table = 'users';

    public $timestamps = false;

    public function getGroupId()
    {
        return $this->group_id;
    }

    public function getSteamId()
    {
        return $this->steam_id;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getAvatar()
    {
        return config('app.avatar_prefix').$this->avatar;
    }

    public function getTimeZone() {
        return $this->time_zone;
    }

    public function getCountry() {
        return $this->country;
    }

    public function getTradeUrl() {
        return $this->trade_url;
    }

    public function getIdVerified() {
        return $this->id_verified;
    }

    public function getJoinDate() {
        return $this->join_date;
    }

    public function getLatestIp() {
        return $this->latest_ip;
    }

    public function getShowWalletAmount() {
        return $this->show_wallet_amount;
    }

    public function getSendSalesReceipts() {
        return $this->send_sales_receipts;
    }

    public function getSendPurchaseReceipts() {
        return $this->send_purchase_receipts;
    }

    public function getBitcoinAddress() {
        return $this->bitcoin_address;
    }

    public function getPayPalEmail() {
        return $this->paypal_email;
    }

    public function getPayPalRestriction1() {
        return $this->paypal_restriction_1;
    }

    public function notifications()
    {
        return $this->hasMany('App\Models\Notifications');
    }

    public function getUnseenNotificationsCount()
    {
        return $this->notifications()->where('seen', 0)->count();
    }

    public function wallet_balance()
    {
        return $this->hasOne('App\Models\WalletBalance');
    }

    public function cart()
    {
        return $this->hasMany('App\Models\Cart');
    }

    public function getCartInfo()
    {
        $cache_minutes = 60;
        $cart_info = [];

        $cart_info['total_count'] =
            Cache::remember('cart_count:uid:'.Auth::id(), $cache_minutes, function () {
                return $this->cart()->count();
            });

        $cart_info['total_cost'] =
            Cache::remember('cart_cost:uid:'.Auth::id(), $cache_minutes, function () {
                return $this->cart()->join('item_sales', 'cart_items.sale_id', '=', 'item_sales.id')->sum('item_sales.price');
            });

        return $cart_info;
    }

    public function item_sales()
    {
        return $this->hasMany('App\Models\ItemSales');
    }

    public function pro_subscription()
    {
        return $this->hasOne('App\Models\ProSubscription');
    }

    public function cashout_requests()
    {
        return $this->hasOne('App\Models\CashoutRequests');
    }

    public function getCashoutRequests ()
    {
        $db =
            $this->cashout_requests()
                ->where('status', '=', config('app.cashout_request_pending'))
                ->select('id', 'method', 'amount', 'send_address')
                ->orderBy('id', 'desc')
                ->get();

        if (!empty($db)) {
            return $db;
        }

        return 0;
    }

    public function updateSalePrice ($sale_id, $new_price)
    {
        $sale = $this->item_sales()->where(['id' => $sale_id, 'status' => config('app.sale_active')])->update(['price' => $new_price]);

        if ($sale) {
            return true;
        }

        return false;
    }

    public function cancelSale ($sale_id)
    {
        $sale = $this->item_sales()->where(['id' => $sale_id, 'status' => config('app.sale_active')])->update(['status' => config('app.sale_cancelled')]);

        if ($sale) {
            return true;
        }

        return false;
    }

    public function boostSale ($sale_id, Transactions $transactions)
    {
        DB::beginTransaction();

        try
        {
            $sale = $this->item_sales()->where(['id' => $sale_id, 'status' => config('app.sale_active'), 'boost' => 0])->select('id')->sharedLock()->first();

            if ($sale) {

                $this->item_sales()->where('id', $sale_id)->update(['boost' => 1]);
                $this->updateBalanceForPurchase(config('app.boost_price'));
                $transactions->newTransaction(Auth::id(), config('app.boost_tid'), config('app.boost_price'));

            } else {
                DB::rollBack();
                return false;
            }

        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }

        DB::commit();
        return true;
    }

    /* If purchase turns balance into negative, because the balance row cannot be negative, it will raise an exception */
    /* This means we do not have to lock the row when checking for say if they can afford a purchase on PHP side */
    // BE CAREFUL with $purchase_amount as it is NOT ESCAPED!!!
    public function updateBalanceForPurchase ($purchase_amount)
    {
        DB::transaction(function () use($purchase_amount) {

            $this->wallet_balance()
                ->decrement('balance', $purchase_amount);

            $this->wallet_balance()
                ->update(['added_funds' => DB::raw('GREATEST(added_funds - ' . $purchase_amount . ', 0)')]);

        });
    }

    public function updateBalanceForAddedFunds ($credit_amount)
    {
        DB::transaction(function () use($credit_amount) {

            $this->wallet_balance()
                ->increment('added_funds', $credit_amount);

            $this->wallet_balance()
                ->increment('balance', $credit_amount);
        });
    }
}
