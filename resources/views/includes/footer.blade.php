<div id="footer-content" class="content">

    <div class="footer-left">
        <a href="{{ route('about') }}">About</a>
        <a href="{{ route('terms') }}">Terms</a>
        <a href="{{ route('privacy') }}">Privacy</a>
    </div>

    <div class="footer-middle-wrap">
        <a class="btn btn-black" href="/feedback">Send Feedback</a>
    </div>

    <div class="footer-right">
        <div>&copy; {{date("Y")}} {{config('app.legal_name')}}</div>
        <span class="footer-poweredby">Powered by <a class="footer-steam-text" href="http://steampowered.com" target="_blank">Steam</a></span>
    </div>

</div>
