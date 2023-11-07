<?php

namespace App\Http\Controllers;
use App\Models\Cart;
use Illuminate\Support\Facades\DB;
use App\Models\ItemSales;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class SalesController extends Controller
{

    public function viewSale($id, SalesController $sales, ItemSales $itemSales, Cart $cartItems)
    {
        $itemsForSale =
            $itemSales->where(['id' => $id, 'private' => 0])->get();

        $itemsForSaleDisplay = $sales->parseItemsForSale($itemsForSale);

        $cart_sale_ids = Auth::check() ? $cartItems->getSaleIds() : [];

        return view(
            'pages.sale',
            [
                'itemsForSaleDisplay' => $itemsForSaleDisplay,
                'cart_sale_ids' => $cart_sale_ids
            ]);
    }

    public function parseItemsForSale ($itemsForSale, $page=0, $cache_minutes=0)
    {
        $suggested_prices_item_names = [];

        foreach ($itemsForSale as $item) {

            $_item_name = $item['name'];

            if (in_array($_item_name, $suggested_prices_item_names) === false) {

                $_exterior = getExteriorTitle($item['exterior']);

                $suggested_prices_item_names_exterior = '';

                if ($_exterior != '') {
                    $suggested_prices_item_names_exterior = ' (' . $_exterior . ')';
                }

                array_push($suggested_prices_item_names, $_item_name.$suggested_prices_item_names_exterior);
            }
        }

        $app_id = getAppId();

        if ($page == 'search') {

            // don't cache suggested prices for search pages
            $item_suggested_prices_array
                = DB::table($app_id.'_prices')->select('market_name', 'price')->whereIn('market_name', $suggested_prices_item_names)->get();

        } else if ($page != 0) {

            // cache browse page suggested prices

            $item_suggested_prices_array =
                Cache::remember('suggested_prices:app_id:'.$app_id.':page:' . $page, $cache_minutes, function () use ($suggested_prices_item_names, $app_id) {
                    return DB::table($app_id.'_prices')->select('market_name', 'price')->whereIn('market_name', $suggested_prices_item_names)->get();
                });
        }


        $i = 0;
        $itemsForSaleDisplay = [];

        foreach ($itemsForSale as $item) {

            $i++;

            // Handle item exterior (field-tested, etc)

            $item_exterior_title_abbr = '';
            $item_exterior_title = '';

            if ($item->exterior != 0) {
                $item_exterior_title_abbr = ' (' . getExteriorTitleAbbr($item->exterior) . ')';
                $item_exterior_title = getExteriorTitle($item->exterior);
            }

            // Handle suggested pricing

            $item_suggested_price = 'N\A';
            $item_percent_off = '';

            $suggested_price_item_exterior = '';

            if ($item_exterior_title != '') {
                $suggested_price_item_exterior = ' (' . $item_exterior_title . ')';
            }

            if ($page) {

                // multiple pages

                foreach ($item_suggested_prices_array as $item_suggested_prices) {

                    if (($item->name . $suggested_price_item_exterior) == $item_suggested_prices->market_name) {

                        $item_suggested_price = $item_suggested_prices->price;

                        $item_percent_off = $this->calcPercentOff($item->price, $item_suggested_prices->price);

                        break;
                    }
                }

            } else {

                // individual item page

                $cache_minutes = 60;
                $item_name = $suggested_prices_item_names[0];

                $item_suggested_price =
                    Cache::remember('suggested_price:app_id:'.$app_id.':name:'.$item_name, $cache_minutes, function () use($item_name, $app_id) {
                        return DB::table($app_id.'_prices')->where('market_name', $item_name)->value('price');
                    });

                if ($item_suggested_price != null) {

                    $item_percent_off = $this->calcPercentOff($item->price, $item_suggested_price);

                } else {

                    $item_suggested_price = 0;
                }
            }

            // Handle wear values

            $display_wear_value = 0;
            $item_wear_value_percent = 0;
            $item_wear_value_percent_not_worn = 0;

            // was the wear value fetched from Steam API successfully?
            if ($item->wear_value_fetched) {

                // if it is 0, that means no wear value exists for this item
                if ($item->wear_value != 0) {

                    // wear value exists for this item, so display it
                    $display_wear_value = 1;

                    // convert wear value to a percent
                    $item_wear_value_percent = number_format(round(($item->wear_value * 100), 2), 2);

                    // calculate how much percent of item is NOT worn
                    $item_wear_value_percent_not_worn = (100 - $item_wear_value_percent);
                }

            }

            $stickers_display = '';

            if ($item->stickers != '') {
                $stickers_display = json_decode($item->stickers, true);
            }

            if ($item->exterior != 0) {
                $market_name = $item->name . ' (' . getExteriorTitle($item->exterior) . ')';
            } else {
                $market_name = $item->name;
            }

            $item_suggested_price = ($item_suggested_price == 0) ? 'N/A' : priceOutput($item_suggested_price);

            $itemsForSaleDisplay[] = [
                'id' => $item->id,
                'user_id' => $item->user_id,
                'name' => $item->name,
                'market_name' => $market_name,
                'name_color' => $item->name_color,
                'exterior' => $item->exterior,
                'grade' => $item->grade,
                'stickers' => $stickers_display,
                'icon_url' => $item->icon_url,
                'inspect_link' => 'steam://rungame/'.$item->inspect_link,
                'price' => $item->price,
                'price_display' => priceOutput($item->price),
                'boost' => $item->boost,
                'status' => $item->status,
                'wear_value' => $item->wear_value,
                'wear_value_fetched' => $item->wear_value_fetched,
                'exterior_title' => $item_exterior_title,
                'exterior_title_abbr' => $item_exterior_title_abbr,
                'suggested_price' => $item_suggested_price,
                'percent_off' => $item_percent_off,
                'display_wear_value' => $display_wear_value,
                'wear_value_percent' => $item_wear_value_percent,
                'wear_value_percent_not_worn' => $item_wear_value_percent_not_worn,

            ];
        }

        return $itemsForSaleDisplay;
    }

    public function calcPercentOff ($price, $suggested_price)
    {
        $item_percent_off = '';

        // calculate difference between price and suggested price
        $item_price_difference = number_format((($suggested_price - $price) / $suggested_price) * 100, 0);

        // only show label for savings >= 5%
        if ($item_price_difference >= 5) {

            $item_percent_off = ($item_price_difference);
        }

        return $item_percent_off;
    }

    public function getItemsEasyNav ()
    {
        return [

            'Knives' => [
                'Bayonet',
                'Butterfly Knife',
                'Falchion Knife',
                'Flip Knife',
                'Gut Knife',
                'Huntsman Knife',
                'Karambit',
                'M9 Bayonet',
                'Shadow Daggers',
                'Bowie Knife',
            ],

            'Pistols' => [
                'CZ75-Auto',
                'Desert Eagle',
                'Dual Berettas',
                'Five-SeveN',
                'Glock-18',
                'P2000',
                'P250',
                'R8 Revolver',
                'Tec-9',
                'USP-S',
            ],

            'Rifles' => [
                'AK-47',
                'AUG',
                'AWP',
                'FAMAS',
                'G3SG1',
                'Galil AR',
                'M4A1-S',
                'M4A4',
                'SCAR-20',
                'SG 553',
                'SSG 08',
            ],

            'SMGs' => [
                'MAC-10',
                'MP7',
                'MP9',
                'PP-Bizon',
                'P90',
                'UMP-45',
            ],

            'Heavy' => [
                'MAG-7',
                'Nova',
                'Sawed-Off',
                'XM1014',
                'M249',
                'Negev',
            ],

            'Cases' => [
                'CS:GO Weapon Case',
                'CS:GO Weapon Case 2',
                'CS:GO Weapon Case 3',
                'Chroma Case',
                'Chroma 2 Case',
                'Chroma 3 Case',
                'Collectible Pins Capsule Series 1',
                'eSports 2013 Case',
                'eSports 2013 Winter Case',
                'eSports 2014 Summer Case',
                'Falchion Case',
                'Gamma Case',
                'Gamma 2 Case',
                'Glove Case',
                'Huntsman Weapon Case',
                'Operation Bravo Case',
                'Operation Breakout Weapon Case',
                'Operation Hydra Case',
                'Operation Phoenix Weapon Case',
                'Operation Vanguard Weapon Case',
                'Operation Wildfire Case',
                'Revolver Case',
                'Shadow Case',
                'Spectrum Case',
                'Winter Offensive Weapon Case',
            ],

            'Keys' => [
                'Chroma Case Key',
                'Chroma 2 Case Key',
                'Chroma 3 Case Key',
                'Community Sticker Capsule 1 Key',
                'CS:GO Capsule Key',
                'CS:GO Case Key',
                'eSports Key',
                'Falchion Case Key',
                'Gamma Case Key',
                'Gamma 2 Case Key',
                'Glove Case Key',
                'Huntsman Case Key',
                'Operation Breakout Case Key',
                'Operation Phoenix Case Key',
                'Operation Vanguard Case Key',
                'Operation Wildfire Case Key',
                'Revolver Case Key',
                'Shadow Case Key',
                'Spectrum Case Key',
                'Winter Offensive Case Key',
            ],

        ];
    }

}
