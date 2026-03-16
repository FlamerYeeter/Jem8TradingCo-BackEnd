<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Product;
use App\Models\Checkout;
use App\Models\Notification;
use App\Models\UserAddress;
use App\Models\Review;
use App\Models\LiveChat;
use App\Models\Message;
use App\Models\Discount;
use Illuminate\Support\Facades\DB;

class Dashboard extends Controller
{
    public function index(){
        return view ('Dashboard',[
            
            'total_views' => Account::count(),
            'new_users' =>Account::whereMonth('created_at', now()->month)
                                    ->whereYear('created_at', now()->year)
                                    ->count(),
            
            'active_users' =>Account::where('is_active', true)->count(),

            'user_this_year' =>Account::selectRaw('Month(created_at) as month, COUNT(*) as total')
                                    ->whereYear('created_at',now()->year)
                                    ->groupBy('month')
                                    ->orderBy('month')
                                    ->pluck('total','month'),

            'users_last_year' =>Account::selectRaw('MONTH(created_at) as month, COUNT(*) as total')
                                    ->whereYear('created_at', now()->year - 1)
                                    ->groupBy('month')
                                    ->orderBy('month')
                                    ->pluck('total', 'month'),


            'recent_orders'    => Checkout::with([
                                    'user',                       // → accounts
                                    'items.product.primaryImage', // → products + product_images (is_primary=1)
                                    'deliveryStatus',             // → delivery_statuses
                                ])
                                ->latest()
                                ->take(6)
                                ->get(),


            'total_orders'     => Checkout::count(),
            'pending_orders'   => Checkout::where('status', 'pending')->count(),
            'completed_orders' => Checkout::where('status', 'completed')->count(),
            'total_revenue'    => Checkout::where('status', 'completed')->sum('total_amount'),

            // This week only
            'weekly_orders'    => Checkout::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'weekly_pending'   => Checkout::where('status', 'pending')->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'weekly_completed' => Checkout::where('status', 'completed')->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),

            'total_products'   => Product::count(),
            'active_products'  => Product::where('status', 'active')->count(),
            'low_stock'        => Product::where('stock_quantity', '<=', 5)->count(),

            'sales_chart'      => Checkout::selectRaw('MONTH(created_at) as month, SUM(total_amount) as revenue')
                                    ->where('status', 'completed')
                                    ->whereYear('created_at', now()->year)
                                    ->groupBy('month')
                                    ->orderBy('month')
                                    ->pluck('revenue', 'month'),
            
            'geo_marketing' => UserAddress::selectRaw('city, COUNT(*) as total')
                                    ->groupBy('city')
                                    ->orderBy('total')
                                    ->take(5)
                                    ->pluck('total','city')

            
        ]);
    }
}
