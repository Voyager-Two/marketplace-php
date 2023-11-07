<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\WalletBalance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Libraries\LightOpenID;

class AuthController extends Controller
{
    public function __construct() {
        $this->middleware('guest')->only('signin');
        $this->middleware('auth')->only('signout');
    }

    public function signin(User $user, WalletBalance $walletBalance, LightOpenID $steam_auth)
    {
        if(!$steam_auth->mode) {

            $steam_auth->identity = 'https://steamcommunity.com/openid';
            return redirect()->away($steam_auth->authUrl());

        } elseif ($steam_auth->mode == 'cancel') {

            return redirect()->route('home')->with('alert', 'Steam authentication was cancelled.');

        } else {

            if ($steam_auth->validate())
            {
                $id = $steam_auth->identity;

                // get steam id from return url
                $ptn = "/^http:\/\/steamcommunity\.com\/openid\/id\/(7[0-9]{15,25}+)$/";
                preg_match($ptn, $id, $matches);

                $steam_api_steam_id = $matches[1];

                $user_id = DB::table('users')->where('steam_id', $steam_api_steam_id)->value('id');

                if (empty($user_id)) {

                    // NEW USER

                    $steam_user_details = $this->getSteamUserDetails($steam_api_steam_id);

                    if ($steam_user_details != false) {

                        $steam_personaname = $steam_user_details[0];
                        $steam_avatar = $this->trimAvatarUrl($steam_user_details[1]);

                        $referral_user_id = session('referral_user_id');

                        DB::beginTransaction();

                        try
                        {
                            // set user data
                            $user->group_id = config('app.standard_gid');
                            $user->steam_id = $steam_api_steam_id;
                            $user->username = $steam_personaname;
                            $user->avatar = $steam_avatar;
                            $user->join_date = time();

                            if ($referral_user_id != null) {
                                // user was referred by another user
                                $user->referral_status = 1;
                            }

                            $user->save();

                            $user_id = $user->id;

                            // create a new wallet
                            $walletBalance->user_id = $user_id;
                            $walletBalance->save();

                            Auth::loginUsingId($user_id, true);

                            // handle referrals

                            if ($referral_user_id != null) {

                                // user was referred by another user
                                DB::table('referrals')
                                    ->insert(
                                        [
                                            'user_id' => $user_id,
                                            'referral_user_id' => $referral_user_id,
                                            'time' => time()
                                        ]
                                    );
                            }

                        } catch (\Exception $e) {

                            DB::rollBack();
                            return redirect()->route('home')->with('alert', 'Something went wrong.');
                        }

                        DB::commit();

                        return redirect()->route('welcome');
                    }

                    return redirect()
                        ->route('home')
                        ->with('alert', 'Failed to retrieve user details from Steam API.');

                } else {

                    // EXISTING USER

                    Auth::loginUsingId($user_id, true);

                    // refresh the users username and avatar

                    $steam_user_details = $this->getSteamUserDetails($steam_api_steam_id);

                    if ($steam_user_details != false) {

                        $steam_personaname = $steam_user_details[0];
                        $steam_avatar = $this->trimAvatarUrl($steam_user_details[1]);

                        $db_group_id = Auth::user()->getGroupId();
                        $db_username = Auth::user()->getUsername();
                        $db_avatar = Auth::user()->getAvatar();

                        // only update if username or avatar has changed
                        if (($db_username != $steam_personaname) || ($db_avatar != $steam_avatar)) {

                            // update username if it has changed
                            // unless user is staff (so we can keep track of staff easily)
                            if (($db_username != $steam_personaname) && !isStaff($db_group_id)) {
                                Auth::user()->username = $steam_personaname;
                            }

                            // update avatar if it has changed
                            if ($db_avatar != $steam_avatar) {
                                Auth::user()->avatar = $steam_avatar;
                            }

                            Auth::user()->save();
                        }

                        return redirect()->route('home')->with('alert', 'Welcome back, '.e($steam_personaname).'.');
                    }

                }

            }

            return redirect()
                ->route('home')
                ->with('alert', 'Steam authentication failed.');

        }

    }

    public function trimAvatarUrl($avatar)
    {
        // trim the unnecessary part of avatar url
        return str_replace('https://steamcdn-a.akamaihd.net/steamcommunity/public/images/avatars/', '', $avatar);
    }

    public function getSteamUserDetails($steam_id)
    {
        $steam_json_url = 'https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=' . config('app.steam_api_key') . '&steamids=' . $steam_id;

        $steam_json_data = getFileContents($steam_json_url, 1);

        if (is_array($steam_json_data)) {

            if (isset($steam_json_data['response']['players'])) {

                $steam_player_info_array = $steam_json_data['response']['players'];

                $steam_personaname = $steam_player_info_array[0]['personaname'];
                $steam_avatar = $steam_player_info_array[0]['avatarfull'];

                return [$steam_personaname,$steam_avatar];
            }
        }

        return false;
    }

    public function signout(Request $request)
    {
        $username = Auth::user()->getUsername();

        Auth::logout();
        $request->session()->flush();

        return redirect()
            ->route('home')
            ->with('alert', 'Signed out. See you later, '.e($username).'.');

    }
}