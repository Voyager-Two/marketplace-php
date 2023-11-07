<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class AjaxController extends Controller
{

    public function __construct() {
        $this->middleware('auth');
    }

    public function actions(Request $request)
    {

        /*
        if (isset($_POST['keep_signed_in'])) {

            $keep_signed_in = $_POST['keep_signed_in'];

            if ($keep_signed_in == 1) {
                $user_id = Auth::id();
                Auth::logout();
                $request->session()->flush();
                $request->session()->regenerate();
                // re-login user and remember them
                Auth::loginUsingId($user_id, true);
                $request->session()->put('ask_keep_signed_in', '0');
            }

            echo 'ok';
            exit;
        }
        */

    }

}