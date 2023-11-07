<?php

/*
| General Helper Functions
*/

function trimTradeUrl($trade_url)
{
    // trim the unnecessary part of trade url
    return str_replace(config('app.trade_url_prefix'), '', $trade_url);
}

function validAssetId($assetid)
{
    if (is_numeric($assetid) && !is_float($assetid) && strlen($assetid) <= 20) {
        return true;
    }
    return false;
}

function parseNotification($nf)
{
    $n_type = $nf->type;

    if ($n_type == config('app.notification_identity_approved')) {

        return 'Your identity verification has been approved.';

    } elseif ($n_type == config('app.notification_identity_not_approved')) {

        return 'Your identity could not be verified, reason: '.getVerifyIdDenialReason($nf->verify_id_reason).'.';

    } elseif ($n_type == config('app.notification_cashout_sent')) {

        return 'Cashout approved, we sent you '.priceOutput($nf->amount).' via '.getCashoutMethodName($nf->cashout_method).'.';

    } elseif ($n_type == config('app.notification_cashout_declined')) {

        return 'Your cashout request was declined ('.priceOutput($nf->amount).' via '.getCashoutMethodName($nf->cashout_method).'). 
                We refunded the exact amount back into your wallet. Please contact us for additional details.';

    } elseif ($n_type == config('app.notification_staff_credit')) {

        return 'A staff member credited your account with '.priceOutput($nf->amount).'.';
    }

    return 'Undefined';
}

function getCashoutMethodName ($id)
{
    if ($id == config('app.paypal_cashout_tid')) {
        return 'PayPal';
    }
    elseif ($id == config('app.bitcoin_cashout_tid')) {
        return 'Bitcoin';
    }

    return 'Undefined';
}

function getVerifyIdDenialReason ($reason_id)
{
    if ($reason_id == 1) {
        return 'bad picture';
    }
    else if ($reason_id == 2) {
        return 'an identity may only be attached to one account, contact us to transfer your identity to a different account';
    }

    return 'please contact support';
}

function getStaffAction($type)
{
    if ($type == config('app.suspended_user')) {
        return 'Suspended user';
    }
    else if ($type == config('app.lifted_suspension')) {
        return 'Lifted suspension';
    }
    else if ($type == config('app.approved_cashout')) {
        return 'Approved cashout';
    }
    else if ($type == config('app.declined_cashout')) {
        return 'Declined cashout';
    }
    else if ($type == config('app.approved_identity')) {
        return 'Approved identity';
    }
    else if ($type == config('app.denied_identity')) {
        return 'Denied identity';
    }
    else if ($type == config('app.denied_identity_auto')) {
        return 'Denied identity (auto)';
    }
    else if ($type == config('app.gave_credit')) {
        return 'Gave credit';
    }
    else if ($type == config('app.staff_login')) {
        return 'Logged into user account';
    }

    return 'Undefined';
}

function randomEmoji ()
{
    $emoji_arr = [
        '👌', '🎅', '🤖', '👻', '😛', '😎', '😉',
        '👽', '😻', '👳', '💪'
    ];

    return $emoji_arr[array_rand($emoji_arr)];
}

function validTradeUrl ($trade_url, $steam_id)
{
    $flat_id = 76561197960265728;
    $first_str = 'https://steamcommunity.com/tradeoffer/new/?partner=';
    $second_str = '&token=';

    if (strpos($trade_url, $first_str) !== false && strpos($trade_url, $second_str) !== false) {

        $parts = parse_url($trade_url);

        parse_str($parts['query'], $query);

        // adding flat id and partner id gives us the user's steam 64-bit id
        if (($flat_id + $query['partner']) == $steam_id) {
            return true;
        }
    }

    return false;
}

function validItemPrice($price)
{
    if (is_numeric($price) && $price <= config('app.max_item_price') && $price >= config('app.min_item_price')) {
        return 1;
    }

    return 0;
}

function priceOutput($price)
{
    return '$'.number_format($price, 2);
}

function isStaff($group_id)
{
    if ($group_id == config('app.staff_gid') || $group_id == config('app.admin_gid')) {
        return true;
    }

    return false;
}

function notRobot()
{
    if (isset($_POST["g-recaptcha-response"])) {
        $response = $_POST["g-recaptcha-response"];
    } else {
        return false;
    }

    $remote_ip = $_SERVER["REMOTE_ADDR"];

    // discard spam submissions
    if (is_null($response) || strlen($response) == 0) {
        return false;
    }

    $post_fields = array(
        'secret' => config('app.recaptcha_private_key'),
        'response' => $response,
        'remoteip' => $remote_ip
    );

    $verification_response = curlPost(config('app.recaptcha_url'),$post_fields,$json=1);

    if (is_array($verification_response)) {

        $success = $verification_response['success'];

        if ($success) {
            return true;
        }

        return false;

    } else {

        return false;
    }
}

function getTransactionTitle ($tid)
{
    if ($tid == config('app.pro_subscription_tid'))
        return 'Pro Subscription';
    else if ($tid == config('app.paypal_cashout_tid'))
        return 'Cashout (PayPal)';
    else if ($tid == config('app.bitcoin_cashout_tid'))
        return 'Cashout (Bitcoin)';
    else if ($tid == config('app.card_funds_tid'))
        return 'Added Funds (Card)';
    else if ($tid == config('app.bitcoin_funds_tid'))
        return 'Added Funds (Bitcoin)';
    else if ($tid == config('app.paypal_funds_tid'))
        return 'Added Funds (PayPal)';
    else if ($tid == config('app.boost_tid'))
        return 'Sale Promotion';
    else if ($tid == config('app.cart_checkout_tid'))
        return 'Item Purchases';
    else if ($tid == config('app.cashout_refund_tid'))
        return 'Cashout Refund';
    else if ($tid == config('app.staff_credit_tid'))
        return 'Staff Credit';
    else if ($tid == config('app.referral_credit_tid'))
        return 'Referral Credit';
    else
        return 'Undefined';
}

function getBotServerIp ($bot_id)
{
    if (app()->environment() == 'local') {

        // local development ip
        return '127.0.0.1';
    }

    $server_1_bots = '1,2,3,4,5';

    // production ips
    if (strpos($server_1_bots, "$bot_id") !== false) {
        return '127.0.0.1'; // 10.0.5.62
    }

    return '127.0.0.1';
}

function getGameShortName ($app_id)
{
    if ($app_id == config('app.dota2'))
        return 'Dota 2';
    else if ($app_id == config('app.h1z1_kotk'))
        return 'H1Z1: KotK';
    else if ($app_id == config('app.pubg'))
        return 'PUBG';
    else
        return 'CS:GO';
}

function getGames ()
{
    return [
        config('app.csgo') => 'Counter-Strike: Global Offensive',
        config('app.dota2') => 'Dota 2',
        config('app.h1z1_kotk') => 'H1Z1: King of the Kill',
        config('app.pubg') => 'PLAYERUNKNOWN\'S BATTLEGROUNDS',
    ];
}

function getInventoryLimit ($app_id)
{
    if ($app_id == config('app.dota2'))
        return 1000;
    else if ($app_id == config('app.h1z1_kotk'))
        return 1000;
    else if ($app_id == config('app.pubg'))
        return 1000;
    else
        return 1000;
}

function getGameTitle ($app_id)
{
    $games = getGames();
    return $games[$app_id];
}

function getGameIcon ($app_id)
{
    if ($app_id == config('app.csgo')) {

        return '/img/csgo_icon.jpg';

    } else if ($app_id == config('app.h1z1_kotk')) {

        return '/img/h1z1_kotk_icon.jpg';

    } else if ($app_id == config('app.dota2')) {

        return '/img/dota2_icon.jpg';

    } else if ($app_id == config('app.pubg')) {

        return '/img/pubg_icon.jpg';
    }

    return '';
}

function getAppId()
{
    $games = getGames();
    $app_id = session('app_id', config('app.csgo'));

    // verify app id to make sure it is a correct value
    if ($app_id < 0 || !isset($games[$app_id])) {

        // default/fallback app id is CS:GO
        session(['app_id' => config('app.csgo')]);
        $app_id = config('app.csgo');
    }

    return $app_id;
}

function getAppContextId($app_id)
{
    // most games have a context id of 2
    $context_id = 2;

    if ($app_id == config('app.h1z1_kotk')) {
        $context_id = 1;
    }

    return $context_id;
}

function processGameChange ($request_app_id)
{
    if ($request_app_id != null) {

        $games = getGames();

        if ($request_app_id > 0 && isset($games[$request_app_id])) {

            if ($request_app_id == config('app.csgo')) {

                session(['app_id' => config('app.csgo')]);

            } elseif ($request_app_id == config('app.dota2')) {

                session(['app_id' => config('app.dota2')]);

            } elseif ($request_app_id == config('app.h1z1_js')) {

                session(['app_id' => config('app.h1z1_js')]);

            } elseif ($request_app_id == config('app.h1z1_kotk')) {

                session(['app_id' => config('app.h1z1_kotk')]);

            } elseif ($request_app_id == config('app.pubg')) {

                session(['app_id' => config('app.pubg')]);
            }
        }
    }
}

function getNewNameColor ($name_color)
{
    $name_color = strtoupper($name_color);

    if ($name_color == 'CF6A32') {

        return 'f58242';

    }  else if ($name_color == '1B723F') {

        return '3cad6a';

    } else if ($name_color == '4D7455') {

        return '77a981';
    }

    return $name_color;
}

function getExteriorTitle ($exterior_id)
{
    if ($exterior_id == 1)
        return 'Factory New';
    else if ($exterior_id == 2)
        return 'Minimal Wear';
    else if ($exterior_id == 3)
        return 'Field-Tested';
    else if ($exterior_id == 4)
        return 'Well-Worn';
    else if ($exterior_id == 5)
        return 'Battle-Scarred';
    else
        return '';
}

function getExteriorTitleAbbr ($exterior_id)
{
    if ($exterior_id == 1)
        return 'FN';
    else if ($exterior_id == 2)
        return 'MW';
    else if ($exterior_id == 3)
        return 'FT';
    else if ($exterior_id == 4)
        return 'WW';
    else if ($exterior_id == 5)
        return 'BS';
    else
        return '';
}

function getExteriorId ($exterior_title)
{
    if ($exterior_title == 'Factory New')
        return 1;
    else if ($exterior_title == 'Minimal Wear')
        return 2;
    else if ($exterior_title == 'Field-Tested')
        return 3;
    else if ($exterior_title == 'Well-Worn')
        return 4;
    else if ($exterior_title == 'Battle-Scarred')
        return 5;
    else
        return 0;
}

function getSuspensionReasonsAndLengths()
{
    return [
        1 => ['Payment Dispute', strtotime('+5000 year')],
        2 => ['Payment Fraud', strtotime('+5000 year')],
        3 => ['Other (please contact support)', strtotime('+3 month')],
    ];
}

function getSuspensionReason($reason_id)
{
    $suspension_info_array = getSuspensionReasonsAndLengths();

    return $suspension_info_array[$reason_id][0];
}

//CURL nodejs post
function curlNodeJs ($fields,$server_ip) {

    $ch = curl_init();

    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_URL => 'https://'.$server_ip,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_PORT => 2088,
        CURLOPT_FOLLOWLOCATION => 0,
        CURLOPT_POSTFIELDS => $fields,
    ));

    $data = curl_exec($ch);

    curl_close($ch);

    return $data;
}
//CURL post
function curlPost ($url,$fields,$json=0) {

    $fields_string = '';
    foreach($fields as $key=>$value) { $fields_string .= $key.'='.urlencode($value).'&'; }
    rtrim($fields_string, '&');

    $ch = curl_init();

    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $url,
        CURLOPT_SSL_VERIFYPEER => 1,
        CURLOPT_FOLLOWLOCATION => 1,
        CURLOPT_POST => count($fields),
        CURLOPT_POSTFIELDS => $fields_string,
    ));

    $data = curl_exec($ch);

    if($json == 1){
        $data = json_decode($data, true);
    }

    curl_close($ch);

    return $data;
}
// CURL alternative to php's file_get_contents
function getFileContents($url,$json=0,$cookie=''){

    $ch = curl_init();

    curl_setopt_array($ch, array(
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $url,
        CURLOPT_SSL_VERIFYPEER => 1,
        CURLOPT_FOLLOWLOCATION => 1,
    ));

    if ($cookie != '') {
        curl_setopt($ch, CURLOPT_COOKIE, $cookie);
    }

    $data = curl_exec($ch);

    if($json == 1){
        $data = json_decode($data, true);
    }

    curl_close($ch);

    return $data;
}

function alert($output, $echo=1) {

    $msg = '<div class="alert-wrap"><div class="alert shake">'.$output.'</div></div>';

    if($echo == 1){
        echo $msg;
    } else {
        return $msg;
    }
}

/* Parses UTC timestamp into human readable time */
function parseTime($timestamp, $time_zone, $type, $concise=0, $full=0) {

    $utcTimeZone = new DateTimeZone('UTC');
    $userTimeZone = new DateTimeZone($time_zone);

    // stamp to date
    $dateTime = date('F j, Y, g:i a', $timestamp);

    $time_object = new DateTime($dateTime, $utcTimeZone);
    $time_object->setTimezone($userTimeZone);
    $date_format = $time_object->format('Y m d');

    if($type == 'time') {

        return $time_object->format('g:i a');

    } else {

        $today = 0;
        $yesterday = 0;

        // we'll need to do this so strtotime functions can work properly
        date_default_timezone_set($time_zone);

        $todayDateTime = date('F j, Y, g:i a', strtotime('today'));
        $oToday = new DateTime($todayDateTime, $userTimeZone);
        $today_date_format = $oToday->format('Y m d');

        $yesterdayDateTime = date('F j, Y, g:i a', strtotime('yesterday'));
        $oYesterday = new DateTime($yesterdayDateTime, $userTimeZone);
        $yesterday_date_format = $oYesterday->format('Y m d');

        // set it back to default otherwise all other time funcs will be messed up
        date_default_timezone_set('UTC');

        if ($date_format == $today_date_format) {

            $today = 1;

        } elseif ($date_format == $yesterday_date_format) {

            $yesterday = 1;
        }

        if($type == 'date') {

            if($today && !$full) {

                return 'Today';

            } elseif($yesterday && !$full) {

                return 'Yesterday';

            } else {

                if ($concise) {

                    $date = $time_object->format('n.j.y');

                } else {

                    $date = $time_object->format('M j, Y');
                }

                return $date;
            }

        } elseif($type == 'dateTime') {

            if($today && !$full) {

                return 'Today, '.$time_object->format('g:i a');

            } elseif($yesterday && !$full) {

                return 'Yesterday, '.$time_object->format('g:i a');

            } else {

                if ($concise) {

                    $date = $time_object->format('n.j.y');

                } else {

                    $date = $time_object->format('M j, Y');

                }

                return $date.', '.$time_object->format('g:i a');
            }
        }
    }

    return 0;
}

/*
* @return an array of time zones
*/
function getTimeZones()
{
    return [
        '(UTC-11:00) Midway Island' => 'Pacific/Midway',
        '(UTC-10:00) Hawaii' => 'Pacific/Honolulu',
        '(UTC-09:00) Alaska' => 'America/Anchorage',
        '(UTC-08:00) Pacific Time' => 'America/Los_Angeles',
        '(UTC-07:00) Arizona' => 'America/Phoenix',
        '(UTC-07:00) Mountain Time' => 'America/Denver',
        '(UTC-06:00) Mexico City' => 'America/Mexico_City',
        '(UTC-06:00) Central Time' => 'America/Chicago',
        '(UTC-05:00) Indiana' => 'America/Indiana/Indianapolis',
        '(UTC-05:00) Eastern Time' => 'America/New_York',
        '(UTC-04:00) Santiago' => 'America/Santiago',
        '(UTC-03:00) Buenos Aires' => 'America/Buenos_Aires',
        '(UTC-01:00) Azores' => 'Atlantic/Azores',
        '(UTC+00:00) Dublin' => 'Europe/Dublin',
        '(UTC+00:00) London' => 'Europe/London',
        '(UTC+00:00) Universal Time' => 'UTC',
        '(UTC+01:00) Amsterdam' => 'Europe/Amsterdam',
        '(UTC+01:00) Berlin' => 'Europe/Berlin',
        '(UTC+01:00) Madrid' => 'Europe/Madrid',
        '(UTC+01:00) Paris' => 'Europe/Paris',
        '(UTC+01:00) Rome' => 'Europe/Rome',
        '(UTC+01:00) Stockholm' => 'Europe/Stockholm',
        '(UTC+01:00) Vienna' => 'Europe/Vienna',
        '(UTC+02:00) Athens' => 'Europe/Athens',
        '(UTC+02:00) Bucharest' => 'Europe/Bucharest',
        '(UTC+02:00) Cairo' => 'Africa/Cairo',
        '(UTC+02:00) Istanbul' => 'Asia/Istanbul',
        '(UTC+02:00) Jerusalem' => 'Asia/Jerusalem',
        '(UTC+03:00) Baghdad' => 'Asia/Baghdad',
        '(UTC+03:00) Kuwait' => 'Asia/Kuwait',
        '(UTC+03:00) Nairobi' => 'Africa/Nairobi',
        '(UTC+03:30) Tehran' => 'Asia/Tehran',
        '(UTC+04:00) Moscow' => 'Europe/Moscow',
        '(UTC+04:00) Kabul' => 'Asia/Kabul',
        '(UTC+05:00) Karachi' => 'Asia/Karachi',
        '(UTC+05:30) Kolkata' => 'Asia/Kolkata',
        '(UTC+05:45) Kathmandu' => 'Asia/Kathmandu',
        '(UTC+06:00) Dhaka' => 'Asia/Dhaka',
        '(UTC+06:30) Rangoon' => 'Asia/Rangoon',
        '(UTC+07:00) Bangkok' => 'Asia/Bangkok',
        '(UTC+08:00) Hong Kong' => 'Asia/Hong_Kong',
        '(UTC+08:00) Singapore' => 'Asia/Singapore',
        '(UTC+09:00) Seoul' => 'Asia/Seoul',
        '(UTC+09:00) Tokyo' => 'Asia/Tokyo',
        '(UTC+09:30) Adelaide' => 'Australia/Adelaide',
        '(UTC+10:00) Guam' => 'Pacific/Guam',
        '(UTC+10:00) Sydney' => 'Australia/Sydney',
        '(UTC+11:00) Vladivostok' => 'Asia/Vladivostok',
        '(UTC+12:00) Fiji' => 'Pacific/Fiji'
    ];
}

function getCountryOptions($country_code)
{
    $countries = getCountryList();
    $countries_options = '';

    foreach($countries as $value => $country_name){
        if ($country_code != $value)
            $countries_options .= '<option value="'.$value.'">'.$country_name.'</option>';
        elseif ($country_code == $value)
            $countries_options .= '<option value="'.$value.'" selected="">'.$country_name.'</option>';
    }

    return $countries_options;
}

/*
* @return an array of countries
*/
function getCountryList()
{
    return [
        'AF' => 'Afghanistan',
        'AX' => 'Aland Islands',
        'AL' => 'Albania',
        'DZ' => 'Algeria',
        'AS' => 'American Samoa',
        'AD' => 'Andorra',
        'AO' => 'Angola',
        'AI' => 'Anguilla',
        'AQ' => 'Antarctica',
        'AG' => 'Antigua and Barbuda',
        'AR' => 'Argentina',
        'AM' => 'Armenia',
        'AW' => 'Aruba',
        'AU' => 'Australia',
        'AT' => 'Austria',
        'AZ' => 'Azerbaijan',
        'BS' => 'Bahamas',
        'BH' => 'Bahrain',
        'BD' => 'Bangladesh',
        'BB' => 'Barbados',
        'BY' => 'Belarus',
        'BE' => 'Belgium',
        'BZ' => 'Belize',
        'BJ' => 'Benin',
        'BM' => 'Bermuda',
        'BT' => 'Bhutan',
        'BO' => 'Bolivia',
        'BQ' => 'Bonaire, Saint Eustatius and Saba',
        'BA' => 'Bosnia and Herzegovina',
        'BW' => 'Botswana',
        'BV' => 'Bouvet Island',
        'BR' => 'Brazil',
        'IO' => 'British Indian Ocean Territory',
        'VG' => 'British Virgin Islands',
        'BN' => 'Brunei',
        'BG' => 'Bulgaria',
        'BF' => 'Burkina Faso',
        'BI' => 'Burundi',
        'KH' => 'Cambodia',
        'CM' => 'Cameroon',
        'CA' => 'Canada',
        'CV' => 'Cape Verde',
        'KY' => 'Cayman Islands',
        'CF' => 'Central African Republic',
        'TD' => 'Chad',
        'CL' => 'Chile',
        'CN' => 'China',
        'CX' => 'Christmas Island',
        'CC' => 'Cocos Islands',
        'CO' => 'Colombia',
        'KM' => 'Comoros',
        'CK' => 'Cook Islands',
        'CR' => 'Costa Rica',
        'HR' => 'Croatia',
        'CU' => 'Cuba',
        'CW' => 'Curacao',
        'CY' => 'Cyprus',
        'CZ' => 'Czech Republic',
        'CD' => 'Democratic Republic of the Congo',
        'DK' => 'Denmark',
        'DJ' => 'Djibouti',
        'DM' => 'Dominica',
        'DO' => 'Dominican Republic',
        'TL' => 'East Timor',
        'EC' => 'Ecuador',
        'EG' => 'Egypt',
        'SV' => 'El Salvador',
        'GQ' => 'Equatorial Guinea',
        'ER' => 'Eritrea',
        'EE' => 'Estonia',
        'ET' => 'Ethiopia',
        'FK' => 'Falkland Islands',
        'FO' => 'Faroe Islands',
        'FJ' => 'Fiji',
        'FI' => 'Finland',
        'FR' => 'France',
        'GF' => 'French Guiana',
        'PF' => 'French Polynesia',
        'TF' => 'French Southern Territories',
        'GA' => 'Gabon',
        'GM' => 'Gambia',
        'GE' => 'Georgia',
        'DE' => 'Germany',
        'GH' => 'Ghana',
        'GI' => 'Gibraltar',
        'GR' => 'Greece',
        'GL' => 'Greenland',
        'GD' => 'Grenada',
        'GP' => 'Guadeloupe',
        'GU' => 'Guam',
        'GT' => 'Guatemala',
        'GG' => 'Guernsey',
        'GN' => 'Guinea',
        'GW' => 'Guinea-Bissau',
        'GY' => 'Guyana',
        'HT' => 'Haiti',
        'HM' => 'Heard Island and McDonald Islands',
        'HN' => 'Honduras',
        'HK' => 'Hong Kong',
        'HU' => 'Hungary',
        'IS' => 'Iceland',
        'IN' => 'India',
        'ID' => 'Indonesia',
        'IR' => 'Iran',
        'IQ' => 'Iraq',
        'IE' => 'Ireland',
        'IM' => 'Isle of Man',
        'IL' => 'Israel',
        'IT' => 'Italy',
        'CI' => 'Ivory Coast',
        'JM' => 'Jamaica',
        'JP' => 'Japan',
        'JE' => 'Jersey',
        'JO' => 'Jordan',
        'KZ' => 'Kazakhstan',
        'KE' => 'Kenya',
        'KI' => 'Kiribati',
        'XK' => 'Kosovo',
        'KW' => 'Kuwait',
        'KG' => 'Kyrgyzstan',
        'LA' => 'Laos',
        'LV' => 'Latvia',
        'LB' => 'Lebanon',
        'LS' => 'Lesotho',
        'LR' => 'Liberia',
        'LY' => 'Libya',
        'LI' => 'Liechtenstein',
        'LT' => 'Lithuania',
        'LU' => 'Luxembourg',
        'MO' => 'Macao',
        'MK' => 'Macedonia',
        'MG' => 'Madagascar',
        'MW' => 'Malawi',
        'MY' => 'Malaysia',
        'MV' => 'Maldives',
        'ML' => 'Mali',
        'MT' => 'Malta',
        'MH' => 'Marshall Islands',
        'MQ' => 'Martinique',
        'MR' => 'Mauritania',
        'MU' => 'Mauritius',
        'YT' => 'Mayotte',
        'MX' => 'Mexico',
        'FM' => 'Micronesia',
        'MD' => 'Moldova',
        'MC' => 'Monaco',
        'MN' => 'Mongolia',
        'ME' => 'Montenegro',
        'MS' => 'Montserrat',
        'MA' => 'Morocco',
        'MZ' => 'Mozambique',
        'MM' => 'Myanmar',
        'NA' => 'Namibia',
        'NR' => 'Nauru',
        'NP' => 'Nepal',
        'NL' => 'Netherlands',
        'NC' => 'New Caledonia',
        'NZ' => 'New Zealand',
        'NI' => 'Nicaragua',
        'NE' => 'Niger',
        'NG' => 'Nigeria',
        'NU' => 'Niue',
        'NF' => 'Norfolk Island',
        'KP' => 'North Korea',
        'MP' => 'Northern Mariana Islands',
        'NO' => 'Norway',
        'OM' => 'Oman',
        'PK' => 'Pakistan',
        'PW' => 'Palau',
        'PS' => 'State of Palestine, Occupied',
        'PA' => 'Panama',
        'PG' => 'Papua New Guinea',
        'PY' => 'Paraguay',
        'PE' => 'Peru',
        'PH' => 'Philippines',
        'PN' => 'Pitcairn',
        'PL' => 'Poland',
        'PT' => 'Portugal',
        'PR' => 'Puerto Rico',
        'QA' => 'Qatar',
        'CG' => 'Republic of the Congo',
        'RE' => 'Reunion',
        'RO' => 'Romania',
        'RU' => 'Russia',
        'RW' => 'Rwanda',
        'BL' => 'Saint Barthelemy',
        'SH' => 'Saint Helena',
        'KN' => 'Saint Kitts and Nevis',
        'LC' => 'Saint Lucia',
        'MF' => 'Saint Martin',
        'PM' => 'Saint Pierre and Miquelon',
        'VC' => 'Saint Vincent and Grenadines',
        'WS' => 'Samoa',
        'SM' => 'San Marino',
        'ST' => 'Sao Tome and Principe',
        'SA' => 'Saudi Arabia',
        'SN' => 'Senegal',
        'RS' => 'Serbia',
        'SC' => 'Seychelles',
        'SL' => 'Sierra Leone',
        'SG' => 'Singapore',
        'SX' => 'Sint Maarten',
        'SK' => 'Slovakia',
        'SI' => 'Slovenia',
        'SB' => 'Solomon Islands',
        'SO' => 'Somalia',
        'ZA' => 'South Africa',
        'GS' => 'South Georgia And Sandwich Isl.',
        'KR' => 'South Korea',
        'SS' => 'South Sudan',
        'ES' => 'Spain',
        'LK' => 'Sri Lanka',
        'SD' => 'Sudan',
        'SR' => 'Suriname',
        'SJ' => 'Svalbard and Jan Mayen',
        'SZ' => 'Swaziland',
        'SE' => 'Sweden',
        'CH' => 'Switzerland',
        'SY' => 'Syria',
        'TW' => 'Taiwan',
        'TJ' => 'Tajikistan',
        'TZ' => 'Tanzania',
        'TH' => 'Thailand',
        'TG' => 'Togo',
        'TK' => 'Tokelau',
        'TO' => 'Tonga',
        'TT' => 'Trinidad and Tobago',
        'TN' => 'Tunisia',
        'TR' => 'Turkey',
        'TM' => 'Turkmenistan',
        'TC' => 'Turks and Caicos Islands',
        'TV' => 'Tuvalu',
        'VI' => 'U.S. Virgin Islands',
        'UG' => 'Uganda',
        'UA' => 'Ukraine',
        'AE' => 'United Arab Emirates',
        'GB' => 'United Kingdom',
        'US' => 'United States',
        'UM' => 'United States Minor Outlying Islands',
        'UY' => 'Uruguay',
        'UZ' => 'Uzbekistan',
        'VU' => 'Vanuatu',
        'VA' => 'Vatican',
        'VE' => 'Venezuela',
        'VN' => 'Vietnam',
        'WF' => 'Wallis and Futuna',
        'EH' => 'Western Sahara',
        'YE' => 'Yemen',
        'ZM' => 'Zambia',
        'ZW' => 'Zimbabwe'
    ];
}

function getTooManyAttemptsOutput ()
{
    return '
            <style>
            body {
                font-family: Arial,"Helvetica Neue",Helvetica,sans-serif;
                background: rgb(31, 31, 31);
            }
            div {
                padding: 5px;
                color: #f5f5f5;
                font-size: 20px;
                font-family: Arial, Helvetica, sans-serif;
            }
            </style>
            <div>
            Too many attempts, please try again later.
            </div>';
}
