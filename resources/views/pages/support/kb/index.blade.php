@extends('layouts.default')

@section('title')
    Knowledge Base &bullet; Support
@stop

@section('content')

    <div class="row">

    <div id="kb_home" class="grid6 center6 module">

        <div class="module-header">

            <div class="module-title">Knowledge Base
                @if($search)
                    - Search
                @endif
            </div>

            <div class="module_btns_wrap">

                <span id="go_back_btn" class="module_btn"><i class="icon-left-arrow"></i> Back</span>

                @if (isStaff($group_id))

                    <a class="module_btn" href="/support/kb/0/edit">New</a>

                @endif

                <span class="dropdown">

                    <span class="module_btn dropdown-toggle module_btn_dropdown_btn_w_search" data-toggle="dropdown" title="Search"></span>

                    <ul class="module-dropdown-menu module-dropdown-menu-search dropdown-menu dropdown-menu-right">
                        <li>
                            <form action="{{ route('support.kb') }}" method="POST">
                                <input  type="text" name="query" value="{{$search_query}}" placeholder="Search" autofocus>
                                {{ csrf_field() }}
                                <input class="hidden" type="submit">
                            </form>
                        </li>
                    </ul>

                </span>

            </div>

        </div>

        <div class="kb_link_list_wrap module-content">

            @if ($kb_articles->count())

                @foreach ($kb_articles as $kb_article)

                    @if (!$kb_article->public)
                        <span class="hint--top" aria-label="Staff Only"><i class="icon-pencil pos-rel-top-1"></i></span>&nbsp;
                    @endif

                    <a class="kb_q_link module_section_link2" href="/support/kb/{{ $kb_article->id }}">{{ $kb_article->title }}</a>

                    @if (!$loop->last)
                        <div class="module_content_divider"></div>
                    @endif

                @endforeach

                @if ($kb_articles->links() != '')

                    <div class="module_content_divider"></div>
                    {{ $kb_articles->links() }}

                @endif

            @else

                @if ($search)
                    No results for <b>{{$search_query}}</b>
                @else
                    No KB articles found.
                @endif

            @endif

        </div>

    </div>

    </div>

@stop