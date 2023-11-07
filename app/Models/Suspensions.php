<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Suspensions extends Model
{
    protected $table = 'suspensions';

    public $timestamps = false;

    public function newSuspension ($user_id, $staff_user_id, $reason, $length)
    {
        $this->user_id = $user_id;
        $this->staff_id = $staff_user_id;
        $this->reason = $reason;
        $this->length = $length;
        $this->time = time();

        $this->save();
    }
}
