@extends('layouts.default')

@section('title')
    Knowledge Base &bullet; Support
@stop

@section('content')

    <div class="row">

    <div class="grid6 center6 module">

        <div class="module-header">

            <div class="module-title"><a href="/support/kb" title="Knowledge Base Home">KB</a> -
                @if ($id != 0)
                    {{ $title }} [EDIT]
                @else
                    New
                @endif
            </div>

            <div class="module_btns_wrap">

                <span id="go_back_btn" class="module_btn"><i class="icon-left-arrow"></i> Back</span>

            </div>

        </div>

        <div class="module-content">

            <form id="publish_article_form" action="{{Request::fullUrl()}}" method="POST">

                <div class="first-input-wrap">
                    <input type="text" name="edit_kb_title" value="{{$title}}" placeholder="Title">
                </div>

                <div class="input-wrap">
                    <textarea id="article_textarea" name="edit_kb_content" placeholder="Content">{{$content}}</textarea>
                </div>

                {{csrf_field()}}

                <div class="last-input-wrap">

                    <div class="left font-size-13">Use of HTML allowed.</div>

                    <div class="right">

                        <select class="select_small" name="edit_public">
                            @if ($public)
                                <option value="0">Staff Only</option>
                                <option value="1" selected>Public</option>
                            @else
                                <option value="0" selected>Staff Only</option>
                                <option value="1">Public</option>
                            @endif
                        </select>

                        <input id="publish_article_btn" class="btn btn-purple" type="submit" value="Save">
                    </div>

                </div>

            </form>

        </div>

    </div>

    </div>

@stop