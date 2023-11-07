<?php

return [

    'name' => 'Marketplace',
    'legal_name' => 'Marketplace.io',

    'env' => env('APP_ENV'),

    'debug' => env('APP_DEBUG'),

    'url' => env('APP_URL'),

    'domain' => env('APP_DOMAIN'),

    'support_email' => env('APP_EMAIL'),

    // used to separate page titles like so: Home - AppName
    'title_separator' => '•',

    /* Groups */
    'suspended_gid' => 0,
    'standard_gid' => 1,
    'pro_gid' => 2,
    'staff_gid' => 3,
    'admin_gid' => 4,

    /* Games */
    'csgo' => 730,
    'dota2' => 570,
    'h1z1_kotk' => 433850,
    'pubg' => 578080,

    /* Sale Fees & Prices*/

    'standard_sale_fee' => 5,
    'pro_sale_fee' => 5,
    'pro_price' => number_format(5.00,2),

    /* Sale Status */

    'sale_active' => 1,
    'sale_sold' => 2,
    'sale_cancelled' => 3,

    /* Sale Options */
    'boost_price' => number_format(3.00,2),
    'max_item_price' => 30000.00,
    'min_item_price' => 0.02,

    'max_referral_sale_commission' => 5.00,

    /************* Wallet *************/

    /* Cashout */
    'min_cashout' => number_format(5.00,2),
    'cashout_request_pending' => 0,

    'cashout_request_approved' => 1,
    'cashout_request_declined' => 2,
    'cashout_request_cancelled' => 3,

    /* Add Funds */
    'min_fund_card' => number_format(5.00, 2),
    'max_fund_card' => number_format(250.00, 2, '.', ''),

    'card_24hr_funds_limit' => number_format(250.00, 2, '.', ''),
    'card_30d_funds_limit' => number_format(2000.00, 2, '.', ''),

    'card_24hr_count_limit' => 3,
    'card_30d_count_limit' => 25,

    'min_fund_bitcoin' => number_format(5.00, 2),
    'max_fund_bitcoin' => number_format(3000.00, 2, '.', ''),

    'min_fund_paypal' => number_format(15.00, 2),
    'max_fund_paypal' => number_format(300.00, 2, '.', ''),
    'paypal_24hr_funds_limit' => number_format(300.00, 2, '.', ''),
    'paypal_30d_funds_limit' => number_format(3000.00, 2, '.', ''),

    'min_fund_g2a' => number_format(5.00, 2),
    'max_fund_g2a' => number_format(5000.00, 2, '.', ''),

    'stripe_api_key' => env('STRIPE_API_KEY'),

    'bitpay_api_key' => env('BITPAY_API_KEY'),
    'bitpay_status_paid' => 1,
    'bitpay_status_confirmed' => 2,

    'paypal_client_id' => env('PAYPAL_CLIENT_ID'),
    'paypal_client_secret' => env('PAYPAL_CLIENT_SECRET'),
    'paypal_sandbox_client_id' => env('PAYPAL_SANDBOX_CLIENT_ID'),
    'paypal_sandbox_client_secret' => env('PAYPAL_SANDBOX_CLIENT_SECRET'),

    'coinbase_api_key' => env('COINBASE_API_KEY'),
    'coinbase_api_secret' => env('COINBASE_API_SECRET'),

    /************ Transactions ***********/
    'pro_subscription_tid' => 1,
    'item_sale_tid' => 2,
    'boost_tid' => 3,
    'cart_checkout_tid' => 4,
    'paypal_cashout_tid' => 5,
    'bitcoin_cashout_tid' => 6,
    'card_funds_tid' => 7,
    'bitcoin_funds_tid' => 8,
    'paypal_funds_tid' => 9,
    'g2a_funds_tid' => 10,
    'cashout_refund_tid' => 11,
    'staff_credit_tid' => 12,
    'referral_credit_tid' => 13,

    /* Payment Orders */
    'paypal_payments_id' => 1,
    'bitpay_payments_id' => 2,

    /* Item Purchase Delivery Status */
    'item_purchase_offer_queued' => 0,
    'item_purchase_offer_sent' => 1,
    'item_purchase_offer_accepted' => 2,
    'item_purchase_offer_declined' => 3,

    /* Cart */
    'max_cart_count' => 30,

    /* Notifications */
    'notification_identity_approved' => 1,
    'notification_identity_not_approved' => 2,
    'notification_cashout_sent' => 3,
    'notification_cashout_declined' => 4,
    'notification_staff_credit' => 5,

    /* Staff Actions */
    'suspended_user' => 1,
    'lifted_suspension' => 2,
    'approved_cashout' => 3,
    'declined_cashout' => 4,
    'approved_identity' => 5,
    'denied_identity' => 6,
    'denied_identity_auto' => 7,
    'gave_credit' => 8,
    'staff_login' => 9,

    /* Steam */
    'avatar_prefix' => 'https://steamcdn-a.akamaihd.net/steamcommunity/public/images/avatars/',
    'trade_url_prefix' => 'https://steamcommunity.com/tradeoffer/new/?',

    // http://steamcommunity.com/dev/apikey
    'steam_api_key' => env('STEAM_API_KEY'),
    'steamlytics_api_key' => env('STEAMLYTICS_API_KEY'),

    /* Recaptcha */
    'recaptcha_public_key' => env('RECAPTCHA_PUBLIC_KEY'),
	'recaptcha_private_key' => env('RECAPTCHA_PRIVATE_KEY'),
	'recaptcha_url' => 'https://www.google.com/recaptcha/api/siteverify',
    'recaptcha_script' => '<script async src=\'https://www.google.com/recaptcha/api.js\'></script>',
    'recaptcha_div' => '<div id="contact_recaptcha_wrap" class="g-recaptcha" data-sitekey="'.env('RECAPTCHA_PUBLIC_KEY').'"></div>',

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. We have gone
    | ahead and set this to a sensible default for you out of the box.
    |
    */

    'timezone' => 'UTC',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
    */

    'locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available. You may change the value to correspond to any of
    | the language folders that are provided through your application.
    |
    */

    'fallback_locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log settings for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Settings: "single", "daily", "syslog", "errorlog"
    |
    */

    'log' => env('APP_LOG', 'single'),

    'log_max_files' => 30,

    'log_level' => env('APP_LOG_LEVEL'),

    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. Feel free to add your own services to
    | this array to grant expanded functionality to your applications.
    |
    */

    'providers' => [

        /*
         * Laravel Framework Service Providers...
         */
        Illuminate\Auth\AuthServiceProvider::class,
        //Illuminate\Broadcasting\BroadcastServiceProvider::class,
        Illuminate\Bus\BusServiceProvider::class,
        Illuminate\Cache\CacheServiceProvider::class,
        Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
        Illuminate\Cookie\CookieServiceProvider::class,
        Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Encryption\EncryptionServiceProvider::class,
        Illuminate\Filesystem\FilesystemServiceProvider::class,
        Illuminate\Foundation\Providers\FoundationServiceProvider::class,
        Illuminate\Hashing\HashServiceProvider::class,
        Illuminate\Mail\MailServiceProvider::class,
        //Illuminate\Notifications\NotificationServiceProvider::class,
        Illuminate\Pagination\PaginationServiceProvider::class,
        Illuminate\Pipeline\PipelineServiceProvider::class,
        Illuminate\Queue\QueueServiceProvider::class,
        Illuminate\Redis\RedisServiceProvider::class,
        //Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,
        Illuminate\Session\SessionServiceProvider::class,
        Illuminate\Translation\TranslationServiceProvider::class,
        Illuminate\Validation\ValidationServiceProvider::class,
        Illuminate\View\ViewServiceProvider::class,
        //Barryvdh\Debugbar\ServiceProvider::class,
        Intervention\Image\ImageServiceProvider::class,

        /*
         * Package Service Providers...
         */
        Laravel\Tinker\TinkerServiceProvider::class,

        /*
         * Application Service Providers...
         */
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        // App\Providers\BroadcastServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\RouteServiceProvider::class,
        App\Providers\ComposerServiceProvider::class,

    ],

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | This array of class aliases will be registered when this application
    | is started. However, feel free to register as many as you wish as
    | the aliases are "lazy" loaded so they don't hinder performance.
    |
    */

    'aliases' => [

        'App' => Illuminate\Support\Facades\App::class,
        'Artisan' => Illuminate\Support\Facades\Artisan::class,
        'Auth' => Illuminate\Support\Facades\Auth::class,
        'Blade' => Illuminate\Support\Facades\Blade::class,
        'Broadcast' => Illuminate\Support\Facades\Broadcast::class,
        'Bus' => Illuminate\Support\Facades\Bus::class,
        'Cache' => Illuminate\Support\Facades\Cache::class,
        'Config' => Illuminate\Support\Facades\Config::class,
        'Cookie' => Illuminate\Support\Facades\Cookie::class,
        'Crypt' => Illuminate\Support\Facades\Crypt::class,
        'DB' => Illuminate\Support\Facades\DB::class,
        'Eloquent' => Illuminate\Database\Eloquent\Model::class,
        'Event' => Illuminate\Support\Facades\Event::class,
        'File' => Illuminate\Support\Facades\File::class,
        'Gate' => Illuminate\Support\Facades\Gate::class,
        'Hash' => Illuminate\Support\Facades\Hash::class,
        'Lang' => Illuminate\Support\Facades\Lang::class,
        'Log' => Illuminate\Support\Facades\Log::class,
        'Mail' => Illuminate\Support\Facades\Mail::class,
        'Notification' => Illuminate\Support\Facades\Notification::class,
        'Password' => Illuminate\Support\Facades\Password::class,
        'Queue' => Illuminate\Support\Facades\Queue::class,
        'Redirect' => Illuminate\Support\Facades\Redirect::class,
        'Redis' => Illuminate\Support\Facades\Redis::class,
        'Request' => Illuminate\Support\Facades\Request::class,
        'Response' => Illuminate\Support\Facades\Response::class,
        'Route' => Illuminate\Support\Facades\Route::class,
        'Schema' => Illuminate\Support\Facades\Schema::class,
        'Session' => Illuminate\Support\Facades\Session::class,
        'Storage' => Illuminate\Support\Facades\Storage::class,
        'URL' => Illuminate\Support\Facades\URL::class,
        'Validator' => Illuminate\Support\Facades\Validator::class,
        'View' => Illuminate\Support\Facades\View::class,
        'Image' => Intervention\Image\Facades\Image::class

    ],

];
