<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

/*
We want to avoid closure based routing here because they are not cached by Laravel
Controllers should handle all of the route logic instead
*/

/***** Authentication Controller *****/
Route::name('auth.signin')->get('/auth', 'AuthController@signin');
Route::name('auth.signout')->post('/signout', 'AuthController@signout');

/***** Static Pages *****/
Route::name('about')->get('/about', 'PagesController@about');
Route::name('terms')->get('/terms', 'PagesController@terms');
Route::name('privacy')->get('/privacy', 'PagesController@privacy');

/***** Home, Search, Profile *****/
Route::name('home')->get('/', 'HomeController@home');
//Route::name('referral')->get('/r/{user_id}', 'HomeController@home');
Route::post('/ajax/disable_front_page_text', 'HomeController@disableFrontPageText');

Route::name('giveaway')->get('/giveaway', 'HomeController@giveaway');

Route::name('search')->get('/search', 'SearchController@index');

/***** Welcome *****/
Route::name('welcome')->get('/welcome', 'WelcomeController@welcome');
Route::name('welcome.post')->post('/welcome', 'WelcomeController@post');

/***** Suspended Message *****/
Route::name('suspended')->get('/suspended', 'SuspendedController@suspended');

/***** Support *****/
Route::name('support')->get('/support', 'SupportController@index');
Route::name('support.contact_form')->post('/support', 'SupportController@contactForm');

/***** KB - Support *****/
Route::name('support.kb')->get('/support/kb', 'SupportController@indexKB');
Route::name('support.kb')->post('/support/kb', 'SupportController@indexKB');
Route::name('support.kb.view')->get('/support/kb/{id}', 'SupportController@viewKB');
Route::get('/support/kb/{id}/edit', 'SupportController@editKB');
Route::post('/support/kb/{id}/edit', 'SupportController@editKB');

/***** Feedback *****/
Route::name('feedback')->get('/feedback', 'FeedbackController@feedback');
Route::post('/feedback', 'FeedbackController@post');

/***** Notifications *****/
Route::name('notifications')->get('/notifications', 'NotificationsController@index');

/***** Settings *****/
Route::name('settings')->get('/settings', 'SettingsController@index');
//Route::name('settings.pro')->get('/settings/pro', 'SettingsController@pro');
//Route::post('/settings/pro', 'SettingsController@proPost');
Route::post('/ajax/settings', 'SettingsController@ajax');

Route::name('verify_id')->get('/verify_id', 'VerifyIdController@verify');
Route::name('verify_id.post')->post('/verify_id', 'VerifyIdController@verify');

/***** Staff Panel *****/
Route::name('staff_panel')->get('/staff_panel/', 'StaffPanelController@index');
Route::name('staff_panel.cashout_requests')->get('/staff_panel/cashout_requests', 'StaffPanelController@cashoutRequests');
Route::name('staff_panel.cashout_requests.post')->post('/staff_panel/cashout_requests', 'StaffPanelController@cashoutRequests');

Route::name('staff_panel.tools')->get('/staff_panel/tools', 'StaffPanelController@tools');
Route::name('staff_panel.tools.ip_search')->get('/staff_panel/tools/ip_search', 'StaffPanelController@toolsIpSearch');
Route::post('/staff_panel/tools/ip_search', 'StaffPanelController@toolsIpSearch');

Route::name('staff_panel.tools.give_credit')->get('/staff_panel/tools/give_credit', 'StaffPanelController@toolsGiveCredit');
Route::post('/staff_panel/tools/give_credit', 'StaffPanelController@toolsGiveCredit');

Route::name('staff_panel.tools.login')->get('/staff_panel/tools/login', 'StaffPanelController@toolsLogin');
Route::post('/staff_panel/tools/login', 'StaffPanelController@toolsLogin');

Route::name('staff_panel.logs')->get('/staff_panel/logs', 'StaffPanelController@logs');
Route::name('staff_panel.feedback')->get('/staff_panel/feedback', 'StaffPanelController@feedback');

/***** Suspensions - Staff Panel *****/
Route::name('staff_panel.suspensions')->get('/staff_panel/suspensions/', 'StaffPanelController@indexSuspensions');
Route::post('/staff_panel/suspensions/', 'StaffPanelController@indexSuspensions');
Route::name('staff_panel.suspensions.lift')->post('/staff_panel/suspensions/lift', 'StaffPanelController@liftSuspension');
Route::name('staff_panel.suspensions.new')->get('/staff_panel/suspensions/new', 'StaffPanelController@newSuspension');
Route::post('/staff_panel/suspensions/new', 'StaffPanelController@newSuspension');
Route::name('staff_panel.verify_id')->get('/staff_panel/verify_id', 'StaffPanelController@verifyId');
Route::name('staff_panel.verify_id.post')->post('/staff_panel/verify_id', 'StaffPanelController@verifyId');

/***** Wallet *****/
Route::name('wallet')->get('/wallet/', 'WalletController@index');
Route::name('wallet.add_funds')->get('/wallet/add_funds', 'WalletController@addFundsView');
Route::name('wallet.cashout')->get('/wallet/cashout', 'WalletController@cashoutView');
Route::name('wallet.cashout_post')->post('/wallet/', 'WalletController@cashoutPost');
Route::name('wallet.transactions')->get('/wallet/transactions', 'WalletController@viewTransactions');

Route::name('wallet.item_purchases')->get('/wallet/item_purchases', 'WalletController@viewItemPurchases');
Route::name('wallet.item_purchases.resend_all')->post('/wallet/item_purchases', 'DeliveryController@resendAll');

/***** Payments *****/
Route::name('payments.stripe')->post('/payments/stripe', 'StripeController@payment');
Route::name('payments.stripe.limit_check')->post('/payments/stripe/limit_check/{id}', 'StripeController@checkPurchaseLimits');

Route::name('payments.paypal')->post('/payments/paypal', 'PayPalController@payment');

Route::name('payments.bitpay')->post('/payments/bitpay', 'BitPayController@payment');
Route::name('payments.bitpay_ipn')->post('/payments/bitpay_ipn', 'BitPayController@IPN');

/***** Manage Sales *****/
Route::name('manage_sales')->get('/manage_sales', 'ManageSalesController@index');
Route::post('/ajax/manage_sales', 'ManageSalesController@ajaxPost');

/***** Individual Sale *****/
Route::name('sale')->get('/sale/{id}', 'SalesController@viewSale');

/***** Steam Inventory *****/
Route::get('/ajax/steam_inventory', 'SteamInventoryController@displayInventory');
Route::get('/ajax/suggested_price', 'SellController@getSuggestedPrice');

/***** Sell *****/
Route::name('sell')->get('/sell', 'SellController@index');
Route::post('/ajax/sell_items', 'SellController@sellItems');

/***** Cart *****/
Route::name('cart')->get('/cart', 'CartController@index');
Route::post('/cart', 'CartController@post');
Route::post('/ajax/cart', 'CartController@ajaxPost');
