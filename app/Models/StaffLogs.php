<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class StaffLogs extends Model
{
    protected $table = 'staff_logs';

    public $timestamps = false;

    public function add($type, $id_verification_id=null, $cashout_request_id=null, $action_user_id=null)
    {
        $this->user_id = Auth::id();
        $this->type = $type;
        $this->id_verification_id = $id_verification_id;
        $this->cashout_request_id = $cashout_request_id;
        $this->action_user_id = $action_user_id;
        $this->time = time();
        $this->save();
    }
}
