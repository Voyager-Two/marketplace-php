<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SuspendedController extends Controller
{

    public function __construct() {
        $this->middleware('auth');
        $this->middleware('throttle:15,1');
    }

    public function suspended() {

        if (Auth::user()->getGroupId() == config('app.suspended_gid')) {

            $suspension_db_array = DB::table('suspensions')->select('reason', 'length')->get();

            $suspension_info_array = getSuspensionReasonsAndLengths();

            $user_suspensions_list_array = [];

            foreach ($suspension_db_array as $suspension_db_data) {

                $reason = $suspension_info_array[$suspension_db_data->reason][0];

                $user_time_zone = Auth::user()->time_zone;

                $time_zone_utc = $user_time_zone == 'UTC' ? ' (UTC)' : '';

                $length = parseTime($suspension_db_data->length, $user_time_zone, 'dateTime');

                $user_suspensions_list_array[] = ['reason' => $reason, 'expires' => $length . $time_zone_utc];
            }

            return view('pages.suspended')
                ->with('user_suspensions_list_array', $user_suspensions_list_array);

        }

        return redirect()->route('home');
    }

}