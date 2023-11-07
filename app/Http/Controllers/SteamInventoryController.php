<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\SearchController;

class SteamInventoryController extends Controller
{
    protected $cache_minutes = 5;
    protected $app_id = 730;
    protected $steam_id = 0;
    protected $raw_inventory = '';
    protected $items = [];

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('throttle:25,1')->only('displayInventory');
    }

    public function displayInventory()
    {
        $this->app_id = getAppId();

        $this->steam_id = Auth::user()->getSteamId();
        $inventory = Cache::get('steam_inventory:app_id:'.$this->app_id.':uid:'.Auth::id());

        if ($inventory == 'empty') {
            return response('<div class="steam_inventory_center_text">Inventory is Empty</div>');
        }

        $total_item_count = 0;

        if ($inventory == null) {

            // inventory not cached
            $this->raw_inventory = $this->getRawInventory();
            $prepared_items = $this->prepareItems();

            if (!empty($prepared_items)) {
                $this->items = $prepared_items['items'];
                $total_item_count = $prepared_items['total_item_count'];
            }

        } else {

            // inventory is cached

            // cached inventory is stored in JSON format
            $this->items = json_decode($inventory, true);
            $total_item_count = count($this->items);
        }

        // can't be empty
        if ($total_item_count == 0) {

            Cache::put('steam_inventory:app_id:'.$this->app_id.':uid:'.Auth::id(), 'empty', $this->cache_minutes);
            return response('<div class="steam_inventory_center_text">Inventory is Empty</div>');

        } else {

            return $this->parseItems();
        }
    }

    protected function parseItems()
    {
        $total_item_count = 0;
        $inventory_html_output = '';

        $items = $this->items;

        // parse our inventory items for output
        foreach ($items as $item) {

            $item_asset_count = count($item['asset']);

            if ($item_asset_count == 1) {

                // just one item don't display how many
                $item_asset_count_display = '';

            } else {

                $item_asset_count_display = '<span id="steam_inventory_item_quantity_wrap">x<span class="steam_inventory_item_quantity">'.$item_asset_count.'</span></span>';
            }

            $item_asset = $item['asset'][0];

            $item_cmmdty = $item['cmmdty'];

            $stkrs_html = '';
            $stkrs_encoded = '';

            if (!empty($item['stkrs'])) {

                foreach ($item['stkrs'] as $sticker) {
                    $stkrs_html .= '<img class="sell_item_sticker" src=' . e($sticker['img']) . ' title="'.e($sticker['title']).'" />';
                }

                // use JSON_FORCE_OBJECT so it has keys (0,1,2,etc)
                $stkrs_encoded = json_encode($item['stkrs'], JSON_FORCE_OBJECT);
            }

            $item_ext_title = '';
            $item_ext_title_abbr = '';

            if ($item['ext'] != 0) {
                $item_ext_title_abbr = ' (' . getExteriorTitleAbbr($item['ext']) . ')';
                $item_ext_title = ' (' . getExteriorTitle($item['ext']) . ')';
            }

            $inventory_html_output .= '
            <div class="steam_inventory_item_wrap" 
                data-assetid="' . e($item_asset) . '" 
                data-name="' . e($item['name']) . '" 
                data-name-color="' . e($item['color']) . '"
                data-grade="' . e($item['grd']) . '" 
                data-stickers="' . $stkrs_encoded . '"
                data-inspect-link="' . e('steam://rungame/'.$item['inspct']) . '" 
                data-quantity="'. $item_asset_count .'" 
                data-commodity="'. e($item_cmmdty) .'" 
                >

                <div class="steam_inventory_item" title="' . e($item['name']) . $item_ext_title . '">
                    '. $item_asset_count_display .'
                    <div class="sell_item_stickers_wrap">'. $stkrs_html .'</div>
                    <img 
                        class="steam_inventory_item_img" 
                        src="https://steamcommunity-a.akamaihd.net/economy/image/' . e($item['icon']) . '/64fx64f" 
                    />
                    <span 
                        class="steam_inventory_item_name" 
                        style="color:#' . e($item['color']) . '">' . e($item['name']) . e($item_ext_title_abbr) . '
                    </span>
                </div>

            </div>
                
            ';

        }

        return response($inventory_html_output.'<div id="steam_inventory_data_div" data-total-item-count="'.$total_item_count.'"></div>');
    }

    public function prepareItems ()
    {
        $doc = new \DOMDocument();

        $total_item_count = 0;

        if (empty($this->raw_inventory) || $this->raw_inventory == 'error' || !isset($this->raw_inventory['assets'])) {
            return [];
        }

        $steam_assets_array = $this->raw_inventory['assets'];
        $items = [];

        // prepare our inventory items
        foreach ($steam_assets_array as $steam_assets) {

            $classid = $steam_assets['classid'];
            $steam_descriptions_array = $this->raw_inventory['descriptions'];

            // handle one individual item
            foreach ($steam_descriptions_array as $steam_descriptions) {

                $item_tradable = $steam_descriptions['tradable'];
                //$item_marketable = $steam_descriptions['marketable'];
                $item_cmmdty = $steam_descriptions['commodity'];

                // must be tradable
                if ($item_tradable) {

                    $item_classid = $steam_descriptions['classid'];

                    // make sure it's the same item (from outer foreach)
                    if ($classid == $item_classid) {

                        $item_asset = $steam_assets['assetid'];
                        $item_app = $steam_assets['appid'];
                        $item_cid = $steam_assets['contextid'];

                        $item_name = $steam_descriptions['name'];
                        $item_market_name = $steam_descriptions['market_name'];
                        $item_icon = $steam_descriptions['icon_url'];
                        $item_grd = $steam_descriptions['type'];

                        $item_color = '';

                        if (isset($steam_descriptions['name_color'])) {
                            $item_color = $steam_descriptions['name_color'];
                            // change name color to brighter colors
                            $item_color = getNewNameColor($item_color);
                        }

                        // parse stickers if CSGO
                        $item_stkrs = '';

                        if ($item_app == config('app.csgo')) {

                            if (!$item_cmmdty) {
                                $item_stkrs = $this->parseStickers($steam_descriptions['descriptions'], $doc);
                            }
                        }

                        $item_slot = 0;
                        $item_cat = 0;

                        if ($item_app == config('app.h1z1_kotk')) {

                            $searchController = new SearchController();

                            if (isset($steam_descriptions['slot'])) {
                                $_options = $searchController->getH1Z1KotKSlot();
                                $item_slot = array_search($steam_descriptions['slot'], $_options) !== false ? $_options[$steam_descriptions['slot']] : 0;
                            }

                            if (isset($steam_descriptions['category'])) {
                                $_options = $searchController->getH1Z1KotKCategory();
                                $item_cat = array_search($steam_descriptions['category'], $_options) !== false ? $_options[$steam_descriptions['category']] : 0;
                            }
                        }

                        $item_hero = 0;

                        if ($item_app == config('app.dota2')) {

                            $searchController = new SearchController();

                            if (isset($steam_descriptions['hero'])) {
                                $_options = $searchController->getDota2Heroes();
                                $item_slot = array_search($steam_descriptions['hero'], $_options) !== false ? $_options[$steam_descriptions['hero']] : 0;
                            }
                        }

                        // if item cmmdty, only display last one
                        if ($item_cmmdty && isset($items[$item_market_name])) {

                            // same item, so just add the asset id to the group, don't do anything else
                            $items[$item_market_name]['asset'][] = $item_asset;
                            break;

                        } else {

                            $total_item_count++;

                            $item_ext_title_abbr = '';
                            $item_ext_title = '';

                            $item_ext = $this->itemExteriorId($item_market_name);

                            if ($item_ext != 0) {
                                $item_ext_title_abbr = ' (' . getExteriorTitleAbbr($item_ext) . ')';
                                $item_ext_title = ' (' . getExteriorTitle($item_ext) . ')';
                            }

                            $item_inspct = '';

                            if (isset($steam_descriptions['actions'][0]['link'])) {
                                $item_inspct = $steam_descriptions['actions'][0]['link'];
                                $item_inspct = str_replace('%owner_steamid%', $this->steam_id, $item_inspct);
                                $item_inspct = str_replace('%assetid%', $item_asset, $item_inspct);
                                $item_inspct = str_replace('steam://rungame/', '', $item_inspct);
                            }

                            // if it is the default item color, just set it to blank instead
                            if ($item_color == 'D2D2D2') {
                                $item_color = '';
                            }

                            $key = $item_asset;

                            if ($item_cmmdty) {
                                $key = $item_market_name;
                            }

                            $items[$key]['asset'][] = $item_asset;
                            $items[$key]['app'] = $item_app;
                            $items[$key]['cid'] = $item_cid;
                            $items[$key]['icon'] = $item_icon;
                            $items[$key]['name'] = $item_name;
                            $items[$key]['color'] = $item_color;
                            $items[$key]['ext'] = $item_ext;
                            $items[$key]['ext_title'] = $item_ext_title;
                            $items[$key]['ext_title_abbr'] = $item_ext_title_abbr;
                            $items[$key]['grd'] = $item_grd;
                            $items[$key]['slot'] = $item_slot;
                            $items[$key]['cat'] = $item_cat;
                            $items[$key]['hero'] = $item_hero;
                            $items[$key]['stkrs'] = $item_stkrs;
                            $items[$key]['inspct'] = $item_inspct;
                            $items[$key]['cmmdty'] = $item_cmmdty;

                            break;
                        }

                    }

                }

            }

        }

        // store $items in cache in JSON format
        $json_items = json_encode($items);
        Cache::put('steam_inventory:app_id:'.$this->app_id.':uid:'.Auth::id(), $json_items, $this->cache_minutes);

        return ['items' => $items, 'total_item_count' => $total_item_count];
    }

    public function getRawInventory()
    {
        // fetch raw inventory from steam

        $context_id = getAppContextId($this->app_id);

        // direct steam api
        $steam_json_url = 'https://steamcommunity.com/inventory/'.$this->steam_id.'/'.$this->app_id.'/'.$context_id.'?l=english';

        // third party api
        //$steam_json_url = 'http://api.steam.steamlytics.xyz/v1/inventory/'.$this->steam_id.'/730/2?key='.config('app.steamlytics_api_key');

        $steam_json_data = getFileContents($steam_json_url,1);

        if (is_array($steam_json_data)) {

            if (isset($steam_json_data['success']) && $steam_json_data['success'] != false) {

                return $steam_json_data;

            } else {
                return 'error';
            }

        } else {
            return 'error';
        }
    }

    protected function parseStickers($details, $doc)
    {
        $item_stkrs = [];

        foreach ($details as $more_description) {

            if (strpos($more_description['value'], 'Sticker:') !== false) {

                $doc->loadHTML($more_description['value']);
                $imageTag = $doc->getElementsByTagName('img');

                preg_match_all('/(Sticker):[^<]+/', $more_description['value'], $sticker_title);

                if (isset($sticker_title[0][0])) {

                    $sticker_title_string = str_replace('Sticker: ', '', $sticker_title[0][0]);

                    $sticker_titles = explode(', ', $sticker_title_string);

                    foreach ($sticker_titles as $key => $sticker_title) {

                        if (isset($imageTag[$key])) {

                            $item_stkrs[$key] = [
                                'title' => $sticker_title,
                                'img' => $imageTag[$key]->getAttribute('src')
                            ];
                        }
                    }
                }
            }
        }

        return $item_stkrs;
    }

    protected function itemExteriorId($item_market_name)
    {
        if (strpos($item_market_name, 'Factory New') !== false)
            return getExteriorId('Factory New');

        elseif (strpos($item_market_name, 'Minimal Wear') !== false)
            return getExteriorId('Minimal Wear');

        elseif (strpos($item_market_name, 'Field-Tested') !== false)
            return getExteriorId('Field-Tested');

        elseif (strpos($item_market_name, 'Well-Worn') !== false)
            return getExteriorId('Well-Worn');

        elseif (strpos($item_market_name, 'Battle-Scarred') !== false)
            return getExteriorId('Battle-Scarred');
        else
            return 0;
    }

}