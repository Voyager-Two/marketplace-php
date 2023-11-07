<?php

namespace App\Http\Controllers;
use App\Models\Notifications;
use App\Models\StaffLogs;
use App\Models\Suspensions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StaffPanelController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('staff');
    }

    public function index ()
    {
        // make sure not all cache timeouts are the same otherwise all queries will run at the same time
        // but also for some stats we want them to be updated at the same time so they can be useful

        $staff_list = Cache::remember('staff_panel_staff_list', 35, function () {
            return DB::table('users')
                ->where('group_id', config('app.staff_gid'))
                ->orWhere('group_id', config('app.admin_gid'))
                ->select('username', 'steam_id')->get();
        });

        $total_users_count = Cache::remember('total_users_count', 5, function () {
            return DB::table('users')->count();
        });

        $total_items_on_sale = Cache::remember('total_items_on_sale', 32, function () {
            return DB::table('item_sales')->where(['status' => config('app.sale_active')])->count();
        });

        $total_items_purchased = Cache::remember('total_items_purchased', 32, function () {
            return DB::table('item_purchases')->count();
        });

        $total_wallet_balance = Cache::remember('total_wallet_balance', 30, function () {
            return DB::table('wallet_balances')->sum('balance');
        });

        $total_purchased_funds = Cache::remember('total_purchased_funds', 30, function () {
            return
                DB::table('transactions')
                    ->where('tid', config('app.card_funds_tid'))
                    ->orWhere('tid', config('app.bitcoin_funds_tid'))
                    ->orWhere('tid', config('app.paypal_funds_tid'))
                    ->orWhere('tid', config('app.g2a_funds_tid'))
                    ->sum('amount');
        });

        $total_added_funds = Cache::remember('total_added_funds', 30, function () {
            return DB::table('wallet_balances')->sum('added_funds');
        });

        $total_cashout_balance = ($total_wallet_balance - $total_added_funds);

        $total_pending_cashout = Cache::remember('total_pending_cashout', 10, function () {
            return DB::table('cashout_requests')->where('status', 0)->sum('amount');
        });

        $total_cashed_out = Cache::remember('total_cashed_out', 10, function () {
            return DB::table('cashout_requests')->where('status', '=', config('app.cashout_request_approved'))->sum('amount');
        });

        $total_sale_commissions = Cache::remember('total_sale_commissions', 28, function () {
            return DB::table('sale_commissions')->sum('amount');
        });

        $total_sale_option_purchases = Cache::remember('total_sale_option_purchases', 15, function () {
            return DB::table('transactions')->where('tid', config('app.boost_tid'))->sum('amount');
        });

        $net_sales = $total_sale_commissions + $total_sale_option_purchases;

        $cash_flow = Cache::remember('cash_flow', 5, function () use($net_sales) {

            $debit = DB::table('transactions')->where('credit', 0)->sum('amount');
            $credit = DB::table('transactions')->where('credit', 1)->sum('amount');

            return ($credit - $debit) + $net_sales;
        });

        $cash_flow_positive = 1;

        if ($cash_flow < 0) {
            $cash_flow_positive = 0;
        }

        return view('pages.staff_panel.index',
            [
                'page' => '',
                'menu_links' => $this->getMenuLinks(),
                'total_users_count' => $total_users_count,
                'staff_list' => $staff_list,
                'total_items_on_sale' => $total_items_on_sale,
                'total_items_purchased' => $total_items_purchased,
                'total_pending_cashout' => $total_pending_cashout,
                'total_cashed_out' => $total_cashed_out,
                'total_wallet_balance' => $total_wallet_balance,
                'total_purchased_funds' => $total_purchased_funds,
                'total_cashout_balance' => $total_cashout_balance,
                'total_sale_commissions' => $total_sale_commissions,
                'total_sale_option_purchases' => $total_sale_option_purchases,
                'cash_flow' => $cash_flow,
                'cash_flow_positive' => $cash_flow_positive
            ]
        );
    }

    public function verifyId (Request $request, StaffLogs $staffLogs, Notifications $notifications)
    {
        if ($request->input('full_name')) {
            $this->postVerifyId($request, $staffLogs, $notifications);
        }

        $verify_id =
            DB::table('id_verification AS idv')
                ->where('status', 0)
                ->join('users AS u', 'idv.user_id', '=', 'u.id')
                ->select('idv.*', 'u.steam_id')
                ->first();

        $verify_img = '';
        $verify_img2 = '';
        $country_options = '';

        if (!empty($verify_id)) {
            $verify_img = Storage::disk('s3')->url($verify_id->img);
            $verify_img2 = Storage::disk('s3')->url($verify_id->img2);
            $country_options = getCountryOptions($verify_id->country);
        }

        $countries = getCountryList();

        return view('pages.staff_panel.verify_id',
            [
                'page' => 'verify_id',
                'menu_links' => $this->getMenuLinks(),
                'verify_id' => $verify_id,
                'verify_img' => $verify_img,
                'verify_img2' => $verify_img2,
                'countries' => $countries,
                'countries_options' => $country_options,
            ]
        );
    }

    public function postVerifyId ($request, $staffLogs, $notifications)
    {
        $id = $request->input('id_verification_id');
        $denied = 0;

        DB::beginTransaction();

        try {

            // has this identity already been approved or denied?
            // lock for update so other users don't take action on same row
            $id_verify =
                DB::table('id_verification')->where('id', $id)
                    ->select('id', 'user_id', 'status', 'img', 'img2')
                    ->lockForUpdate()->first();

            if ($id_verify != null && $id_verify->status === 0) {

                if ($request->input('approve')) {

                    // identity approved

                    $full_name = $request->input('full_name');
                    $date_of_birth = $request->input('date_of_birth');
                    $country = $request->input('country');

                    $id_exists =
                        DB::table('id_verification')
                            ->where('id', '!=', $id)
                            ->where(['date_of_birth' => $date_of_birth, 'full_name' => $full_name, 'country' => $country])
                            ->value('id');

                    if ($id_exists != null) {

                        // Deny: this identity is already attached to another account

                        $denied = 1;

                        // notify user
                        $notifications->insert(
                            [
                                'user_id' => $id_verify->user_id,
                                'type' => config('app.notification_identity_not_approved'),
                                'verify_id_reason' => 2
                            ]
                        );

                        $staffLogs->add(config('app.denied_identity_auto'), $id_verify->id);

                        session(['alert' => 'Auto-denied. Reason: identity already attached to another account.']);

                    } else {

                        // save name, date of birth, country
                        DB::table('id_verification')
                            ->where('id', $id)
                            ->update(
                                [
                                    'status' => 1,
                                    'full_name' => $full_name,
                                    'date_of_birth' => $date_of_birth,
                                    'country' => $country,
                                    'img' => null,
                                    'img_data' => null,
                                    'img2' => null,
                                    'img2_data' => null
                                ]
                            );

                        // handle user account
                        DB::table('users')->where('id', $id_verify->user_id)->update(['id_verified' => 1]);

                        // notify user
                        $notifications->insert(
                            [
                                'user_id' => $id_verify->user_id,
                                'type' => config('app.notification_identity_approved')
                            ]
                        );

                        // add to staff logs
                        $staffLogs->add(config('app.approved_identity'), $id_verify->id);
                    }

                } else if ($request->input('bad_picture') || $request->input('deny_other')) {

                    // Denied (Bad Picture or Other Reasons)

                    $denied = 1;

                    // notify user
                    $notifications->insert(
                        [
                            'user_id' => $id_verify->user_id,
                            'type' => config('app.notification_identity_not_approved'),
                            'verify_id_reason' => $request->input('bad_picture') ? 1 : 3
                        ]
                    );

                    // add to staff logs
                    $staffLogs->add(config('app.denied_identity'), $id_verify->id);
                }

                if ($denied) {
                    // delete column
                    DB::table('id_verification')->where('id', $id)->delete();

                    // handle user account
                    DB::table('users')->where('id', $id_verify->user_id)->update(['id_verified' => 0]);
                }

                // delete s3 pictures
                Storage::disk('s3')->delete($id_verify->img);
                Storage::disk('s3')->delete($id_verify->img2);
            }

            DB::commit();

        } catch (\Exception $e) {

            DB::rollBack();
            session(['alert' => 'Something went wrong, please try again.']);
        }
    }

    public function cashoutRequests (Request $request, StaffLogs $staffLogs, Notifications $notifications)
    {
        if ($request->input('cashout_request_id')) {
            $this->postCashoutRequests($request, $staffLogs, $notifications);
        }

        $cashout_request =
            DB::table('cashout_requests AS cr')
                ->where('status', 0)
                ->join('users AS u', 'cr.user_id', '=', 'u.id')
                ->select('u.id AS user_id', 'u.steam_id', 'u.username', 'u.country', 'cr.id', 'cr.method', 'cr.amount', 'cr.send_address', 'cr.time')
                ->first();

        $cashout_method_text = '';
        $total_cashout_method = 0;
        $total_cashout = 0;
        $total_in_sales = 0;
        $country_list = getCountryList();

        if ($cashout_request != null) {

            $total_cashout_method =
                DB::table('cashout_requests')
                    ->where(
                        [
                            'user_id' => $cashout_request->user_id,
                            'status' => 1,
                            'method' => $cashout_request->method,
                        ]
                    )
                    ->sum('amount');

            $total_cashout =
                DB::table('cashout_requests')
                    ->where(
                        [
                            'user_id' => $cashout_request->user_id,
                            'status' => 1,
                        ]
                    )
                    ->sum('amount');

            $total_in_sales =
                DB::table('item_sales')
                    ->where(
                        [
                            'user_id' => $cashout_request->user_id,
                            'status' => config('app.sale_sold'),
                        ]
                    )
                    ->sum('price');

            $cashout_method_text = $cashout_request->method == config('app.paypal_cashout_tid') ? 'PayPal' : 'Bitcoin';
        }

        return view('pages.staff_panel.cashout_requests',
            [
                'page' => 'cashout_requests',
                'menu_links' => $this->getMenuLinks(),
                'cashout_request' => $cashout_request,
                'cashout_method_text' => $cashout_method_text,
                'total_cashout_method' => $total_cashout_method,
                'total_cashout' => $total_cashout,
                'total_in_sales' => $total_in_sales,
                'country_list' => $country_list
            ]
        );
    }

    public function postCashoutRequests ($request, $staffLogs, $notifications)
    {
        $id = $request->input('cashout_request_id');

        DB::beginTransaction();

        try {

            // has cashout request already been approved or denied?
            // lock for update so other users don't take action on same row
            $cashout_request =
                DB::table('cashout_requests')
                    ->where('id', $id)
                    ->select('id', 'user_id', 'status', 'method', 'amount', 'send_address')
                    ->lockForUpdate()->first();

            if ($cashout_request != null && $cashout_request->status === 0) {

                if ($request->input('approve')) {

                    // cashout was sent to the user

                    // change cashout status
                    DB::table('cashout_requests')->where('id', $id)->update(['status' => config('app.cashout_request_approved')]);

                    // notify user
                    $notifications->insert(
                        [
                            'user_id' => $cashout_request->user_id,
                            'type' => config('app.notification_cashout_sent'),
                            'amount' => $cashout_request->amount,
                            'cashout_method' => $cashout_request->method,
                        ]
                    );

                    // add to staff logs
                    $staffLogs->add(config('app.approved_cashout'), null, $cashout_request->id);

                } else if ($request->input('decline')) {

                    // decline and refund cashout request

                    // change cashout status
                    DB::table('cashout_requests')->where('id', $id)->update(['status' => config('app.cashout_request_declined')]);

                    // refund the amount to user wallet
                    DB::table('wallet_balances')
                        ->where('user_id', $cashout_request->user_id)
                        ->increment('balance', $cashout_request->amount);

                    // insert new transaction for the user
                    DB::table('transactions')
                        ->insert(
                            [
                                'user_id' => $cashout_request->user_id,
                                'tid' => config('app.cashout_refund_tid'),
                                'amount' => $cashout_request->amount,
                                'credit' => 1,
                                'time' => time()
                            ]
                        );

                    // notify user
                    $notifications->insert(
                        [
                            'user_id' => $cashout_request->user_id,
                            'type' => config('app.notification_cashout_declined'),
                            'amount' => $cashout_request->amount,
                            'cashout_method' => $cashout_request->method,
                        ]
                    );

                    // add to staff logs
                    $staffLogs->add(config('app.declined_cashout'), null, $cashout_request->id);
                }

            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            session(['alert' => 'Something went wrong, please try again.']);
        }
    }

    public function tools ()
    {
        return view('pages.staff_panel.tools.index',
            [
                'page' => 'tools',
                'menu_links' => $this->getMenuLinks()
            ]
        );
    }

    public function toolsGiveCredit (Request $request, Notifications $notifications, StaffLogs $staffLogs)
    {
        if (!empty($request->input('credit_amount'))) {

            $this->validate($request, [
                'credit_user_id_or_steam_id' => 'required|int',
                'credit_amount' => 'required|numeric'
            ]);

            // must be admin
            if (Auth::user()->getGroupId() != config('app.admin_gid')) {
                return redirect()->route('staff_panel.tools.give_credit')->with('alert', 'Access denied');
            }

            $credit_user_id_or_steam_id = $request->input('credit_user_id_or_steam_id');
            $credit_amount = $request->input('credit_amount');
            $max_credit = 25;

            if ($credit_amount > $max_credit) {
                return redirect()->route('staff_panel.tools.give_credit')->with('alert', 'Credit must be less than '.priceOutput($max_credit));
            }

            DB::beginTransaction();

            try
            {

                if (count($credit_user_id_or_steam_id) < 12) {

                    // user id
                    $credit_user_id = DB::table('users')->where('id', $credit_user_id_or_steam_id)->value('id');

                } else {

                    // steam id
                    $credit_user_id = DB::table('users')->where('steam_id', $credit_user_id_or_steam_id)->value('id');
                }

                if ($credit_user_id == null) {
                    return redirect()->route('staff_panel.tools.give_credit')->with('alert', '<i class="icon-x"></i> Invalid User ID or Steam ID.');
                }

                DB::table('wallet_balances')
                    ->where('user_id', $credit_user_id)
                    ->increment('added_funds', $credit_amount);

                DB::table('wallet_balances')
                    ->where('user_id', $credit_user_id)
                    ->increment('balance', $credit_amount);

                // insert new transaction for the user
                DB::table('transactions')
                    ->insert(
                        [
                            'user_id' => $credit_user_id,
                            'tid' => config('app.staff_credit_tid'),
                            'amount' => $credit_amount,
                            'credit' => 1,
                            'time' => time()
                        ]
                    );

                // notify user
                $notifications->insert(
                    [
                        'user_id' => $credit_user_id,
                        'type' => config('app.notification_staff_credit'),
                        'amount' => $credit_amount
                    ]
                );

                // add to staff logs
                $staffLogs->add(config('app.gave_credit'), null, null, $credit_user_id);

                DB::commit();
                session(['alert' => '<i class="icon-ok"></i> Credited '.priceOutput($credit_amount).' to user.']);

            } catch (\Exception $e) {

                DB::rollBack();

                session(['alert' => '<i class="icon-x"></i> Failed to give credit, try again soon.']);
            }

        }

        return view('pages.staff_panel.tools.give_credit',
            [
                'page' => 'tools',
                'menu_links' => $this->getMenuLinks(),
            ]
        );
    }

    public function toolsIpSearch (Request $request)
    {
        $search_results = '';
        $search_type = '';

        if (!empty($request->input('ip_address_or_user_id'))) {

            $search_query = $request->input('ip_address_or_user_id');

            if (is_numeric($search_query)) {

                // assume they provided user id instead of ip
                // return all ips for a user

                $search_type = 'user_id';

                $search_results =
                    DB::table('users_ip_history')->where('user_id', $search_query)
                        ->select('ip', 'time')->get();

            } else {

                // assume they have provided an ip address
                // return all users that accessed the site with that ip

                $this->validate($request, [
                    'ip_address_or_user_id' => 'required|ip'
                ],
                    [
                       'ip_address_or_user_id' => 'Invalid IP address'
                    ]
                );

                $search_type = 'ip';
                $ip_binary = inet_pton($search_query);

                $search_results =
                    DB::table('users_ip_history AS uph')
                        ->where('ip', $ip_binary)
                        ->join('users AS u', 'uph.user_id', '=', 'u.id')
                        ->select('uph.time', 'u.id AS user_id', 'u.username', 'u.steam_id')
                        ->get();
            }

        }

        return view('pages.staff_panel.tools.ip_search',
            [
                'page' => 'tools',
                'menu_links' => $this->getMenuLinks(),
                'search_results' => $search_results,
                'search_type' => $search_type
            ]
        );
    }

    public function toolsLogin (Request $request, StaffLogs $staffLogs)
    {
        if (!empty($request->input('credit_user_id_or_steam_id'))) {

            $this->validate($request, [
                'credit_user_id_or_steam_id' => 'required|int',
            ]);

            // must be admin
            if (Auth::user()->getGroupId() != config('app.admin_gid')) {
                return redirect()->route('staff_panel.tools.login')->with('alert', 'Access denied');
            }

            $credit_user_id_or_steam_id = $request->input('credit_user_id_or_steam_id');

            if (count($credit_user_id_or_steam_id) < 12) {

                // user id
                $login_user_id = DB::table('users')->where('id', $credit_user_id_or_steam_id)->value('id');

            } else {

                // steam id
                $login_user_id = DB::table('users')->where('steam_id', $credit_user_id_or_steam_id)->value('id');
            }

            // Make sure it is a valid user
            if ($login_user_id == null) {
                return redirect()->route('staff_panel.tools.login')->with('alert', '<i class="icon-x"></i> Invalid User ID or Steam ID.');
            }

            // add to staff logs
            $staffLogs->add(config('app.staff_login'), null, null, $login_user_id);

            // Logout of admin account
            Auth::logout();
            $request->session()->flush();

            // Login to user account
            Auth::loginUsingId($login_user_id, true);

            $login_username = Auth::user()->getUsername();

            return redirect()->route('home')->with('alert', 'Logged into the account of '.$login_username);
        }

        return view('pages.staff_panel.tools.login',
            [
                'page' => 'tools',
                'menu_links' => $this->getMenuLinks(),
            ]
        );
    }

    public function indexSuspensions (Suspensions $suspensions, Request $request)
    {
        $time_zone = Auth::user()->time_zone;

        $display_sort = 'newest';
        $asc_or_desc = 'desc';
        $sort_link = $request->fullUrl().'?sort=o';

        if ($request->input('page')) {
            $sort_link = $request->fullUrl().'&sort=o';
        }

        if ($request->input('sort') == 'o') {
            $display_sort = 'oldest';
            $asc_or_desc = 'asc';
        }

        $search = 0;
        $search_query = '';

        if ($request->input('query')) {
            $search = 1;
            $search_query = $request->input('query');
        }

        $suspensions_data =
            $suspensions
                ->where('suspensions.expired', 0)
                ->when($search, function ($query) use ($search_query) {
                    return $query->where('u1.steam_id', 'like', '%' . $search_query . '%');
                })
                ->join('users as u1', 'suspensions.user_id', '=', 'u1.id')
                ->join('users as u2', 'suspensions.staff_id', '=', 'u2.id')
                ->select(
                    'suspensions.id',
                    'suspensions.user_id',
                    'suspensions.reason',
                    'suspensions.length',
                    'u1.steam_id',
                    'u2.username'
                )
                ->orderBy('suspensions.id', $asc_or_desc)
                ->paginate(10);

        $suspensions_list = [];

        $suspension_info_array = getSuspensionReasonsAndLengths();

        foreach($suspensions_data as $suspension) {

            $reason = $suspension_info_array[$suspension->reason][0];

            $length = parseTime($suspension->length, $time_zone, 'dateTime');

            $suspensions_list[] = [

                'id' => $suspension->id,
                'user_id' => $suspension->user_id,
                'steam_id' => $suspension->steam_id,
                'staff_username' => $suspension->username,
                'reason' => $reason,
                'length' => $length,

            ];

        }

        return view('pages.staff_panel.suspensions.index',
            [
                'page' => 'suspensions',
                'menu_links' => $this->getMenuLinks(),
                'suspensions_data' => $suspensions_data,
                'suspensions_list' => $suspensions_list,
                'display_sort' => $display_sort,
                'sort_link' => $sort_link,
                'search' => $search,
                'search_query' => $search_query,
            ]
        );
    }

    public function newSuspension (Suspensions $suspensions, Request $request, StaffLogs $staffLogs)
    {
        $suspension_info_array = getSuspensionReasonsAndLengths();
        $suspension_reason_options = '';

        foreach ($suspension_info_array as $suspension_reason_id => $suspension_reason_info) {
            $suspension_reason_options .= '<option value="'.$suspension_reason_id.'">'.$suspension_reason_info[0].'</option>';
        }

        if ($request->input('id') && $request->input('reason_id')) {

            $id = $request->input('id');
            $suspension_reason_id = $request->input('reason_id');

            if (isset($suspension_info_array[$suspension_reason_id])) {

                // we need to figure out if our staff entered a user id or a steam id

                // user ids are less than 11 characters
                if (count($id) < 11) {
                    // user id
                    $suspension_user_id = DB::table('users')->where('id', $id)->value('id');
                } else {
                    // steam id
                    $suspension_user_id = DB::table('users')->where('steam_id', $id)->value('id');
                }

                $staff_user_id = Auth::id();

                if ($suspension_user_id != null) {

                    $suspension_length = $suspension_info_array[$suspension_reason_id][1];

                    $suspensions->newSuspension($suspension_user_id,$staff_user_id,$suspension_reason_id,$suspension_length);

                    DB::table('users')->where('id', $suspension_user_id)->update(['group_id' => config('app.suspended_gid')]);

                    $staffLogs->add(config('app.suspended_user'));

                    return redirect()->route('staff_panel.suspensions')->with('alert', 'User suspended.');

                } else {

                    return redirect()->route('staff_panel.suspensions.new')->with('alert', 'User not found.');
                }
            }
        }

        return view('pages.staff_panel.suspensions.new',
            [
                'page' => 'suspensions',
                'menu_links' => $this->getMenuLinks(),
                'suspension_reason_options' => $suspension_reason_options
            ]
        );
    }

    public function liftSuspension (Suspensions $suspensions, Request $request, StaffLogs $staffLogs)
    {
        if ($request->input('lift_suspension')) {

            $suspension_id = $request->input('lift_suspension');

            $suspension = $suspensions->find($suspension_id);
            $suspension->expired = 1;
            $suspension->save();

            $staffLogs->add(config('app.lifted_suspension'));

            return redirect()->route('staff_panel.suspensions')->with('alert', 'Suspension lifted.');
        }

        return redirect()->route('staff_panel.suspensions');
    }

    public function logs ()
    {
        $logs =
            DB::table('staff_logs AS sl')
                ->join('users AS u', 'sl.user_id', '=', 'u.id')
                ->select('u.username', 'sl.type', 'sl.time')
                ->orderBy('sl.id', 'desc')
                ->paginate(18);

        return view('pages.staff_panel.logs',
            [
                'page' => 'logs',
                'menu_links' => $this->getMenuLinks(),
                'logs' => $logs
            ]
        );
    }

    public function feedback ()
    {
        $feedback = DB::table('feedback')->orderBy('id', 'desc')->paginate(18);

        return view('pages.staff_panel.feedback',
            [
                'page' => 'feedback',
                'menu_links' => $this->getMenuLinks(),
                'feedback' => $feedback
            ]
        );
    }

    public function getMenuLinks ()
    {
        return [
            ['page' => '', 'name' => 'Staff Panel'],
            ['page' => 'verify_id', 'name' => 'ID Verification'],
            ['page' => 'cashout_requests', 'name' => 'Cashout Requests'],
            ['page' => 'tools', 'name' => 'Tools'],
            ['page' => 'suspensions', 'name' => 'Suspensions'],
            ['page' => 'feedback', 'name' => 'Feedback'],
            ['page' => 'logs', 'name' => 'Logs']
        ];
    }

}