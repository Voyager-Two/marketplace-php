<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;

class ComposerServiceProvider extends ServiceProvider
{

    public function boot()
    {
        view()->composer('pages.*', function ($view)
        {
            $cart_total_count = 0;
            $cart_total_cost = 0;

            if (Auth::check())
            {
                $cart_info = Auth::user()->getCartInfo();
                $cart_total_count = $cart_info['total_count'];
                $cart_total_cost = $cart_info['total_cost'];
            }

            $app_id = getAppId();

            $view->with
            (
                [
                    'cart_total_count' => $cart_total_count,
                    'cart_total_cost' => $cart_total_cost,
                    'app_id' => $app_id
                ]
            );
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //
    }

}