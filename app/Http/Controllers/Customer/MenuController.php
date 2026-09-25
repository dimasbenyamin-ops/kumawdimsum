<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MenuController extends Controller
{
    // Allow-list of valid category values
    private const VALID_CATEGORIES = ['bundling_hemat', 'original', 'spicy_mayo', 'goreng_keju', 'premium_sauce', 'sharing_party', 'snacks', 'minuman', 'add_on'];

    private const CATEGORY_LABELS = [
        'bundling_hemat' => '🏷️ Bundling Hemat',
        'original'      => '🥟 Original',
        'spicy_mayo'    => '🌶️ Spicy Mayo',
        'goreng_keju'   => '🧀 Goreng Keju',
        'premium_sauce' => '🍯 Premium Sauce',
        'sharing_party' => '🎉 Sharing Party',
        'snacks'        => '🍟 Snacks',
        'minuman'       => '🍵 Minuman',
        'add_on'        => '➕ Add On',
    ];

    public function index(Request $request): View
    {
        $category = $request->query('category');

        // Default to bundling_hemat if not specified
        if ($category === null) {
            $category = 'bundling_hemat';
        } elseif ($category !== 'all' && ! in_array($category, self::VALID_CATEGORIES, true)) {
            $category = 'bundling_hemat';
        }

        // Cache all available menus in Redis to eliminate DB latency
        $allAvailableMenus = \Illuminate\Support\Facades\Cache::remember('customer_all_available_menus', 3600, function () {
            return Menu::available()->ordered()->get();
        });

        // Group / filter by category for section display
        if ($category !== 'all') {
            $filtered = $allAvailableMenus->where('category', $category)->values();
            $menus = collect([$category => $filtered]);
        } else {
            $menus = $allAvailableMenus->groupBy('category');
        }

        $cart = session('cart', []);
        $cartCount = count($cart);

        return view('customer.menu.index', [
            'menus'          => $menus,
            'categoryLabels' => self::CATEGORY_LABELS,
            'activeCategory' => $category,
            'cartCount'      => $cartCount,
            'cart'           => $cart,
        ]);
    }
}
