<?php

namespace App\Http\Controllers;
use App\Models\Cart;
use App\Models\ItemSales;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SearchController extends Controller
{
    public function __construct()
    {
        $this->middleware('throttle:25,1');
    }

    public function index(Request $request, SalesController $sales, Cart $cartItems)
    {
        $app_id = getAppId();
        $name = '';

        if ($request->input('name')) {
            $name = $request->input('name');
        }

        $sort = 0;
        if ($request->input('sort'))
        {
            $sort = $request->input('sort');
            $sort_options = $this->getSort();

            $sort = !isset($sort_options[$sort]) ? 0 : $sort;
        }

        $min_price = 0;
        if ($request->input('min_price'))
        {
            $min_price = $request->input('min_price');

            // can't be less than or equal to our app's min price setting
            $min_price = ($min_price <= config('app.min_item_price')) ? 0 : $min_price;

            // can't be more than or equal to our app's max price setting
            $min_price = ($min_price >= config('app.max_item_price')) ? 0 : $min_price;
        }

        $max_price = 0;
        if ($request->input('max_price'))
        {
            $max_price = $request->input('max_price');

            // can't be less than or equal to our app's min price setting
            $max_price = ($max_price <= config('app.min_item_price')) ? 0 : $max_price;

            // can't be more than our app's max price setting
            $max_price = ($max_price > config('app.max_item_price')) ? 0 : $max_price;
        }

        $page = $request->has('page') ? $request->query('page') : 1;
        $page = (!is_numeric($page)) ? 1 : $page;

        /*
         *
        $cache_minutes = 2;
        $cache_id = 'search_items:page:'.$page.
            ':name:'.$name.
            ':sort:'.$sort.
            ':grade:'.$grade.
            ':exterior:'.$exterior.
            ':stattrack:'.$stattrack.
            ':min_price:'.$min_price.
            ':max_price:'.$max_price;

        Cache::remember(
        $cache_id, $cache_minutes,
                        function () use(&$user_id,&$name,&$sort,&$grade,&$exterior,&$stattrack,&$min_price,&$max_price)
                        {
                            return
        */

        $itemsForSale = new ItemSales;

        $itemsForSale = $itemsForSale
            ->where([
                ['appid', $app_id],
                ['status', config('app.sale_active')],
                ['private', 0],
                ['name', 'like', '%' . $name . '%']
            ])
            ->orderBy('boost', 'desc')
            ->when($sort == 1, function ($query) {
                return $query->orderBy('price', 'asc');
            })
            ->when($sort == 2, function ($query) {
                return $query->orderBy('price', 'desc');
            })
            ->when($sort == 4, function ($query) {
                return $query->orderBy('id', 'desc');
            });

        if ($app_id == config('app.csgo')) {

            // set this to -1 instead because 0 is taken
            $exterior = -1;
            if ($request->input('exterior')) {
                $exterior = $request->input('exterior');
                $exterior_options = $this->getExterior();

                $exterior = !isset($exterior_options[$exterior]) ? -1 : $exterior;

                // none/vanilla is actually 0 in database
                $exterior = ($exterior == 6) ? 0 : $exterior;
            }

            $type = -1;
            if ($request->input('type')) {
                $type = $request->input('type');
                $type_options = $this->getType();

                $type = !isset($type_options[$type]) ? -1 : $type;
            }

            $grade = -1;
            if ($request->input('grade')) {
                $grade = $request->input('grade');
                $grade_options = $this->getGrade();

                $grade = !isset($grade_options[$grade]) ? -1 : $grade;

                // Others is actually 0 in database
                $grade = ($grade == 1337) ? 0 : $grade;
            }

            $stickers = 0;
            if ($request->input('stattrack')) {
                $stickers = $request->input('stickers');
                $stickers_options = $this->getStickers();

                $stickers = !isset($stickers_options[$stickers]) ? 0 : $stickers;
            }


            $stattrack = 0;
            if ($request->input('stattrack')) {
                $stattrack = $request->input('stattrack');
                $stattrack_options = $this->getStatTrack();

                $stattrack = !isset($stattrack_options[$stattrack]) ? 0 : $stattrack;
            }

            $itemsForSale = $itemsForSale
                ->when($sort == 3, function ($query) {
                    return $query->orderBy('wear_value', 'asc');
                })
                ->when($type != -1, function ($query) use (&$type) {
                    return $query->where('type', $type);
                })
                ->when($grade != -1, function ($query) use (&$grade) {
                    return $query->where('grade_id', $grade);
                })
                ->when($exterior != -1, function ($query) use (&$exterior) {
                    return $query->where('exterior', $exterior);
                })
                ->when($stickers == 1, function ($query) {
                    return $query->where('stickers', '!=', '');
                })
                ->when($stickers == 2, function ($query) {
                    return $query->where('stickers', '=', '');
                })
                ->when($stattrack == 1, function ($query) {
                    return $query->where('stattrack', 1);
                })
                ->when($stattrack == 2, function ($query) {
                    return $query->where('stattrack', 0);
                });

        } else if ($app_id == config('app.h1z1_kotk')) {

            $h1z1_slot = -1;
            if ($request->input('slot')) {
                $h1z1_slot = $request->input('slot');
                $h1z1_options = $this->getH1Z1KotKSlot();

                $h1z1_slot = !isset($h1z1_options[$h1z1_slot]) ? -1 : $h1z1_slot;
            }

            $h1z1_category = -1;
            if ($request->input('category')) {
                $h1z1_category = $request->input('category');
                $h1z1_category_options = $this->getH1Z1KotKCategory();

                $h1z1_category = !isset($h1z1_category_options[$h1z1_category]) ? -1 : $h1z1_category;
            }

            $itemsForSale = $itemsForSale
                ->when($h1z1_slot != -1, function ($query) use (&$h1z1_slot) {
                    return $query->where('slot', $h1z1_slot);
                })
                ->when($h1z1_category != -1, function ($query) use (&$h1z1_category) {
                    return $query->where('category', $h1z1_category);
                });

        } else if ($app_id == config('app.dota2')) {

            $dota2_hero = -1;
            if ($request->input('hero')) {
                $dota2_hero = $request->input('hero');
                $dota2_heroes = $this->getDota2Heroes();

                $dota2_hero = !isset($dota2_heroes[$dota2_hero]) ? -1 : $dota2_hero;
            }

            $itemsForSale = $itemsForSale
                ->when($dota2_hero != -1, function ($query) use (&$dota2_hero) {
                    return $query->where('hero', $dota2_hero);
                });
        }

        $itemsForSale = $itemsForSale
                ->when($min_price != 0, function ($query) use (&$min_price) {
                    return $query->where('price', '>=', $min_price);
                })
                ->when($max_price != 0, function ($query) use (&$max_price) {
                    return $query->where('price', '<=', $max_price);
                })
                ->simplePaginate(20);

        $itemsForSaleDisplay = $sales->parseItemsForSale($itemsForSale, $page='search');

        $cart_sale_ids = Auth::check() ? $cartItems->getSaleIds() : [];

        $return_array =
            [
                'itemsForSaleDisplay' => $itemsForSaleDisplay,
                'itemsForSale' => $itemsForSale,
                'sort_options' => $this->getSort(),
                'cart_sale_ids' => $cart_sale_ids
            ];

        if ($app_id == config('app.csgo')) {

            $return_array = array_merge($return_array, [
                'item_easy_nav' => $sales->getItemsEasyNav(),
                'exterior_options' => $this->getExterior(),
                'type_options' => $this->getType(),
                'grade_options' => $this->getGrade(),
                'stickers_options' => $this->getStickers(),
                'stattrack_options' => $this->getStatTrack()
            ]);

        } elseif ($app_id == config('app.h1z1_kotk')) {

            $return_array = array_merge($return_array, [
                'h1z1_kotk_slot_options' => $this->getH1Z1KotKSlot(),
                'h1z1_kotk_category_options' => $this->getH1Z1KotKCategory()
            ]);

        } elseif ($app_id == config('app.dota2')) {

            $return_array = array_merge($return_array, [
                'dota2_heroes_options' => $this->getDota2Heroes()
            ]);
        }

        return view('pages.search', $return_array);
    }

    public function getSort()
    {
        $app_id = getAppId();

        if ($app_id == config('app.csgo')) {

            return [
                '' => 'Relevance',
                1 => 'Lowest Price',
                2 => 'Highest Price',
                3 => 'Lowest Wear',
                4 => 'Newest'
            ];

        } else {

            return [
                '' => 'Relevance',
                1 => 'Lowest Price',
                2 => 'Highest Price',
                4 => 'Newest'
            ];
        }

    }

    public function getExterior()
    {
        return [
            ''=> 'Any',
            1 => 'Factory New',
            2 => 'Minimal Wear',
            3 => 'Field-Tested',
            4 => 'Well-Worn',
            5 => 'Battle-Scarred',
            6 => 'None/Vanilla'
        ];
    }

    public function getType()
    {
        return [
            ''=> 'Any',
            1 => 'Knife',
            2 => 'Pistol',
            3 => 'Rifle',
            4 => 'Shotgun',
            5 => 'SMG',
            6 => 'Machine Gun',
            7 => 'Container',
            8 => 'Key',
            9 => 'Sticker',
            10 => 'Gloves',
            11 => 'Graffiti',
        ];
    }

    public function getGrade()
    {
        return [
            ''=> 'Any',
            1 => 'Consumer',
            2 => 'Industrial',
            3 => 'Mil-Spec',
            4 => 'Restricted',
            5 => 'Classified',
            6 => 'Covert',
            7 => 'Exceedingly Rare',
            1337 => 'Others'
        ];
    }

    public function getStatTrack()
    {
        return [
            ''=> 'Ok',
            1 => 'Only',
            2 => 'None'
        ];
    }

    public function getStickers()
    {
        return [
            ''=> 'Ok',
            1 => 'Only',
            2 => 'None'
        ];
    }

    public function getH1Z1KotKSlot()
    {
        return [
            ''=> 'Any',
            1 => 'Armor',
            2 => 'Back',
            3 => 'Chest',
            4 => 'Eyes',
            5 => 'Face',
            6 => 'Feet',
            7 => 'Hands',
            8 => 'Head',
            9 => 'Legs',
        ];
    }

    public function getH1Z1KotKCategory()
    {
        return [
            ''=> 'Any',
            1 => 'Ammunition',
            2 => 'Armor',
            3 => 'Construction',
            4 => 'Construction Socket Bound',
            5 => 'Crafting',
            6 => 'Create Recipe',
            7 => 'Drink',
            8 => 'Drinks',
            9 => 'Emotes',
            10 => 'Event',
            11 => 'Explosives',
            12 => 'Food',
            13 => 'Generic',
            14 => 'Ingredient',
            15 => 'Material',
            16 => 'Materials',
            17 => 'Medical',
            18 => 'Misc',
            19 => 'Not Implemented',
            20 => 'Quests',
            21 => 'Schematics',
            22 => 'Skin',
            23 => 'Skins',
            24 => 'Storage',
            25 => 'Tools',
            26 => 'Traps',
            27 => 'Vehicle Parts',
            28 => 'Weapons',
            29 => 'Wearable',
            30 => 'Wearables',
        ];
    }

    public function getDota2Heroes()
    {
        return [
            ''=> 'Any',
            1 => 'Abaddon',
            2 => 'Alchemist',
            3 => 'Ancient Apparition',
            4 => 'Anti-Mage',
            5 => 'Arc Warden',
            6 => 'Axe',
            7 => 'Bane',
            8 => 'Batrider',
            9 => 'Beastmaster',
            10 => 'Bloodseeker',
            11 => 'Bounty Hunter',
            12 => 'Brewmaster',
            13 => 'Bristleback',
            14 => 'Broodmother',
            15 => 'Centaur Warrunner',
            16 => 'Chaos Knight',
            17 => 'Chen',
            18 => 'Clinkz',
            19 => 'Clockwerk',
            20 => 'Crystal Maiden',
            21 => 'Dark Seer',
            22 => 'Dazzle',
            23 => 'Death Prophet',
            24 => 'Disruptor',
            25 => 'Doom',
            26 => 'Dragon Knight',
            27 => 'Drow Ranger',
            28 => 'Earth Spirit',
            29 => 'Earthshaker',
            30 => 'Elder Titan',
            31 => 'Ember Spirit',
            32 => 'Enchantress',
            33 => 'Enigma',
            34 => 'Faceless Void',
            35 => 'Gyrocopter',
            36 => 'Huskar',
            37 => 'Invoker',
            38 => 'Io',
            39 => 'Jakiro',
            40 => 'Juggernaut',
            41 => 'Keeper of the Light',
            42 => 'Kunkka',
            43 => 'Legion Commander',
            44 => 'Leshrac',
            45 => 'Lich',
            46 => 'Lifestealer',
            47 => 'Lina',
            48 => 'Lion',
            49 => 'Lone Druid',
            50 => 'Luna',
            51 => 'Lycan',
            52 => 'Magnus',
            53 => 'Medusa',
            54 => 'Meepo',
            55 => 'Mirana',
            56 => 'Monkey King',
            57 => 'Morphling',
            58 => 'Naga Siren',
            59 => 'Nature\'s Prophet',
            60 => 'Necrophos',
            61 => 'Night Stalker',
            62 => 'Nyx Assassin',
            63 => 'Ogre Magi',
            64 => 'Omniknight',
            65 => 'Oracle',
            66 => 'Outworld Devourer',
            67 => 'Phantom Assassin',
            68 => 'Phantom Lancer',
            69 => 'Phoenix',
            70 => 'Puck',
            71 => 'Pudge',
            72 => 'Pugna',
            73 => 'Queen of Pain',
            74 => 'Razor',
            75 => 'Riki',
            76 => 'Rubick',
            77 => 'Sand King',
            78 => 'Shadow Demon',
            79 => 'Shadow Fiend',
            80 => 'Shadow Shaman',
            81 => 'Silencer',
            82 => 'Skywrath Mage',
            83 => 'Slardar',
            84 => 'Slardar',
            85 => 'Sniper',
            86 => 'Spectre',
            87 => 'Spirit Breaker',
            88 => 'Storm Spirit',
            89 => 'Sven',
            90 => 'Techies',
            91 => 'Templar Assassin',
            92 => 'Terrorblade',
            93 => 'Tidehunter',
            94 => 'Timbersaw',
            95 => 'Tinker',
            96 => 'Tiny',
            97 => 'Treant Protector',
            98 => 'Troll Warlord',
            99 => 'Tusk',
            100 => 'Underlord',
            101 => 'Undying',
            102 => 'Ursa',
            103 => 'Vengeful Spirit',
            104 => 'Venomancer',
            105 => 'Viper',
            106 => 'Visage',
            107 => 'Warlock',
            108 => 'Weaver',
            109 => 'Windranger',
            110 => 'Winter Wyvern',
            111 => 'Witch Doctor',
            112 => 'Wraith King',
            113 => 'Zeus',
        ];
    }

}