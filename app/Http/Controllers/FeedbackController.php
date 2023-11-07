<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeedbackController extends Controller
{

    public function __construct()
    {
        $this->middleware('throttle:6,60')->only('post');
    }

    public function feedback ()
    {
        return view('pages.feedback');
    }

    public function post (Request $request)
    {
        if ($request->input('not_in_my_house') == '') {

            $post_message = $request->input('message');

            $this->validate($request, [
                'message' => 'required|max:5000'
            ]);

            DB::table('feedback')->insert(['message' => $post_message, 'time' => time()]);

            return redirect()->route('feedback')->with('alert', 'You\'re awesome, thank you! 🌼');
        }

        return view('pages.feedback');
    }

}