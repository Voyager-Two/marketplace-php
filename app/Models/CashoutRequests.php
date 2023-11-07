<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashoutRequests extends Model
{
    protected $table = 'cashout_requests';

    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

}