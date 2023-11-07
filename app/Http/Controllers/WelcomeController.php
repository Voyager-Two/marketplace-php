<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class WelcomeController extends Controller
{
    public function __construct() {
        $this->middleware('auth');
    }

    public function welcome()
    {
        if (Auth::user()->getEmail() != null) {
            return redirect()->route('home');
        }

        return view('pages.welcome');
    }

    public function post(Request $request)
    {
        $user_id = Auth::id();

        if (Auth::user()->getEmail() == null) {

            $post_contact_email = $request->input('contact_email');

            $this->validate($request, [
                'contact_email' => 'required|email|max:255'
            ]);

            $connection_ip = $request->ip();

            // we store IP addresses in our database in binary form
            // which allows us to support both IPv4 and IPv6
            $connection_ip_binary = inet_pton($connection_ip);

            DB::beginTransaction();

            try
            {
                DB::table('users_ip_history')->insert(['user_id' => $user_id, 'ip' => $connection_ip_binary, 'time' => time()]);

                Auth::user()->email = $post_contact_email;
                Auth::user()->latest_ip = $connection_ip_binary;

                // get country code from CloudFlare
                if (!empty($_SERVER['HTTP_CF_IPCOUNTRY'])) {
                    $country_code = $_SERVER['HTTP_CF_IPCOUNTRY'];
                    Auth::user()->country = $country_code;
                }

                Auth::user()->save();

            } catch (\Exception $e) {

                DB::rollBack();
                redirect()->route('welcome')->with('alert', 'Something went wrong. Please try again later.');
            }

            DB::commit();

            return redirect()->route('home')->with('alert', '<i class="icon-checkmark"></i> Thank you, you can now purchase and sell items.');
        }

        return redirect()->route('home');
    }
}