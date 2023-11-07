<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProSubscription extends Model
{

    protected $table = 'pro_subscriptions';

    public $timestamps = false;

    public function getAutoRenew()
    {
        return $this->auto_renew;
    }

    public function getEndTime()
    {
        return $this->end_time;
    }

    public function newSubscription($user_id, $auto_renew, $end_time)
    {
        $this->user_id = $user_id;
        $this->auto_renew = $auto_renew;
        $this->start_time = time();
        $this->end_time = $end_time;

        $this->save();
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

}