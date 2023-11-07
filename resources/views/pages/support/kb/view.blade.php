@extends('layouts.default')

@section('title')
    Knowledge Base &bullet; Support
@stop

@section('content')

<div class="row">

<div class="grid6 center6 module">

    <div class="module-header">

        <div class="module-title"><a href="/support/kb" title="Knowledge Base Home">KB</a> - {{ $title }}
            @if (!$public)
                &nbsp;<span class="hint--right" aria-label="Staff Only"><i class="icon-pencil pos-rel-top-1"></i></span>&nbsp;
            @endif
        </div>

        <div class="module_btns_wrap">

            <span id="go_back_btn" class="module_btn"><i class="icon-left-arrow"></i> Back</span>

            @if (isStaff($group_id))
                <a class="module_btn" href="/support/kb/{{$id}}/edit"><i class="icon-pencil module_btn_icon"></i>Edit</a>
            @endif

        </div>

    </div>

    <div class="module-content">
        {!! $content !!}
    </div>

</div>

</div>

@stop