<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transactions extends Model
{
    protected $table = 'transactions';

    public $timestamps = false;

    public function newTransaction($user_id, $transaction_id, $amount, $cashout_request_id=null)
    {
        $this->user_id = $user_id;
        $this->tid = $transaction_id;
        $this->amount = $amount;
        $this->cashout_request_id = $cashout_request_id;
        $this->time = time();

        $this->save();
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }
}