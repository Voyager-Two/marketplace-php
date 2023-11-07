<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class WalletBalance extends Model
{
    protected $table = 'wallet_balances';

    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    public function getBalance()
    {
        return $this->balance;
    }

    public function getAddedFunds()
    {
        return $this->added_funds;
    }

    public function updateBalanceForAddedFunds ($user_id, $credit_amount)
    {
        DB::transaction(function () use($user_id, $credit_amount) {

            $this->where('user_id', $user_id)
                ->increment('balance', $credit_amount);

            $this->where('user_id', $user_id)
                ->increment('added_funds', $credit_amount);
        });
    }

}