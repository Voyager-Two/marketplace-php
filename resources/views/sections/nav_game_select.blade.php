@php
    $games = getGames();
@endphp

@foreach ($games as $app_id => $game_title)
    <li><a href="?app_id={{$app_id}}">{{$game_title}}</a></li>
@endforeach