<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactForm;
use App\Models\KnowledgeBase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupportController extends Controller
{

    public function __construct()
    {
        $this->middleware('throttle:5,30')->only('contactForm');
        $this->middleware('throttle:25,1')->only('viewKB', 'searchKB');
        $this->middleware('staff')->only('editKB');
    }

    public function index (Request $request)
    {
        if (Auth::check()) {

            $steam_id = $request->old('steam_id') ?: Auth::user()->getSteamId();
            $email = $request->old('email') ?: Auth::user()->getEmail();

        } else {

            $steam_id = $request->old('steam_id') ?: '';
            $email = $request->old('email') ?: '';
        }

        return view('pages.support.index', [
            'steam_id' => $steam_id,
            'email' => $email
        ]);
    }

    public function contactForm (Request $request)
    {
        if ($request->input('not_in_my_house') == '') {

            $user_id = 0;
            $post_steam_id = $request->input('steam_id');
            $post_contact_email = $request->input('contact_email');
            $post_message = $request->input('message');

            $this->validate($request, [
                'steam_id' => 'nullable|integer',
                'contact_email' => 'required|email',
                'message' => 'required|max:5000'
            ]);

            if (Auth::check() && !empty($post_steam_id)) {

                $user_id = Auth::id();
                $user_steam_id = Auth::user()->getSteamId();

                // verify that the steam id user entered matches signed in steam id
                if ($post_steam_id == $user_steam_id) {

                    $steam_id_verification = 'Verified';

                } else {

                    $steam_id_verification = 'This Steam ID does not match signed-in user Steam ID (' . $user_steam_id.')';
                }

            } else {

                $steam_id_verification = 'Not entered';
            }

            Mail::to(config('app.support_email'))->queue(new ContactForm($post_contact_email,$user_id,$post_steam_id,$steam_id_verification,$post_message));

            return redirect()->route('support')->with('alert', '<i class="icon-checkmark"></i> Got it! We\'ll get back to you soon. 🌺');
        }

        return redirect()->route('support');
    }

    /* Knowledge Base */

    public function indexKB (KnowledgeBase $kb, Request $request)
    {
        $group_id = 0;

        if (Auth::check()) {
            $group_id = Auth::user()->getGroupId();
        }

        $search = 0;
        $search_query = '';

        if ($request->input('query')) {
            $search = 1;
            $search_query = $request->input('query');
        }

        $kb_articles =
            $kb->when(isStaff($group_id) == false, function ($query) {
                    return $query->where('public', 1);
                })
                ->when($search, function ($query) use ($search_query) {
                    return $query->where('title', 'like', '%' . $search_query . '%')->orWhere('content', 'like', '%' . $search_query . '%');
                })
                ->orderBy('views', 'desc')
                ->select('id', 'title', 'content', 'public')
                ->paginate(10);

        return view('pages.support.kb.index', [
            'kb_articles' => $kb_articles,
            'search' => $search,
            'search_query' => $search_query,
            'group_id' => $group_id
        ]);
    }

    public function viewKB ($id, KnowledgeBase $kb)
    {
        $group_id = 0;

        if (Auth::check()) {
            $group_id = Auth::user()->getGroupId();
        }

        $kb_article =
            $kb->where(['id' => $id])
                ->when(isStaff($group_id) == false, function ($query) {
                    return $query->where('public', 1);
                })
                ->select('title', 'content', 'public')
                ->first();

        if (!empty($kb_article)) {

            $kb_title = $kb_article->title;
            $kb_content = nl2br($kb_article->content);
            $kb_public = $kb_article->public;

            $kb->incrementView($id);

            return view(
                'pages.support.kb.view',
                [
                    'id' => $id,
                    'title' => $kb_title,
                    'content' => $kb_content,
                    'public' => $kb_public,
                    'group_id' => $group_id
                ]
            );

        } else {

            return redirect()->route('support.kb')->with('alert', 'Article not found.');
        }
    }

    public function editKB ($id, KnowledgeBase $kb, Request $request)
    {
        // save edited article
        if ($request->input('edit_kb_title') && $request->input('edit_kb_content')) {

            if ($id == 0) {

                $id = $kb->createArticle($request->input('edit_kb_title'), $request->input('edit_kb_content'), $request->input('edit_public'));

            } else {
                $kb->updateArticle($id, $request->input('edit_kb_title'), $request->input('edit_kb_content'), $request->input('edit_public'));
            }

            return redirect()->route('support.kb.view', [$id])->with('alert', 'Article saved.');
        }

        if ($id == 0) {

            // show blank edit form so user can crate new article
            return view('pages.support.kb.edit', ['id' => 0, 'title' => '', 'content' => '', 'public' => 0]);

        } else {

            // show existing article in editable form

            $kb_article = $kb->where('id', $id)->select('title', 'content', 'public')->first();

            if (!empty($kb_article)) {

                $kb_title = $kb_article->title;
                $kb_content = $kb_article->content;
                $kb_public = $kb_article->public;

                return view('pages.support.kb.edit', ['id' => $id, 'title' => $kb_title, 'content' => $kb_content, 'public' => $kb_public]);

            } else {

                return redirect()->route('support.kb')->with('alert', 'Article not found.');
            }
        }

    }

}