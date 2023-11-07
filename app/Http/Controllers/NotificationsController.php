<?php

namespace App\Http\Controllers;
use App\Models\Notifications;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationsController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $user =  Auth::user();
        $paginate = 0;

        if ($request->input('all')) {

            // show all notifications
            $notifications = $user->notifications()->orderBy('id', 'desc')->paginate(15);
            $paginate = 1;

        } else {

            // show new notifications
            $notifications = $user->notifications()->where('seen', 0)->orderBy('id', 'desc')->get();

            // mark new notifications as seen
            $user->notifications()->where('seen', 0)->update(['seen' => 1]);
        }

        return view(
            'pages.notifications',
            [
                'notifications' => $notifications,
                'paginate' => $paginate
            ]);

    }

}