<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use App\Models\Account;
use App\Models\Checkout;
use App\Models\UserAddress;
use App\Models\Contact;
use App\Models\Product;
use App\Models\Notifications;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;


class Dashboard extends Controller
{
    // public function index(){
    //     return view ('dashboard',[

    //         'total_views' => Account::count(),
    //         'new_users' =>Account::whereMonth('created_at', now()->month)
    //                                 ->whereYear('created_at', now()->year)
    //                                 ->count(),

    //         'active_users' =>Account::where('is_active', true)->count(),

    //         'user_this_year' =>Account::selectRaw('Month(created_at) as month, COUNT(*) as total')
    //                                 ->whereYear('created_at',now()->year)
    //                                 ->groupBy('month')
    //                                 ->orderBy('month')
    //                                 ->pluck('total','month'),

    //         'users_last_year' =>Account::selectRaw('MONTH(created_at) as month, COUNT(*) as total')
    //                                 ->whereYear('created_at', now()->year - 1)
    //                                 ->groupBy('month')
    //                                 ->orderBy('month')
    //                                 ->pluck('total', 'month'),


    //         'recent_orders'    => Checkout::with([
    //                                 'user',                       // → accounts
    //                                 'items.product.primaryImage', // → products + product_images (is_primary=1)
    //                                 'deliveryStatus',             // → delivery_statuses
    //                             ])
    //                             ->latest()
    //                             ->take(6)
    //                             ->get(),


    //         'total_orders'     => Checkout::count(),
    //         'pending_orders'   => Checkout::where('status', 'pending')->count(),
    //         'completed_orders' => Checkout::where('status', 'completed')->count(),
    //         'total_revenue'    => Checkout::where('status', 'completed')->sum('total_amount'),

    //         // This week only
    //         'weekly_orders'    => Checkout::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
    //         'weekly_pending'   => Checkout::where('status', 'pending')->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
    //         'weekly_completed' => Checkout::where('status', 'completed')->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),

    //         'total_products'   => Product::count(),
    //         'active_products'  => Product::where('status', 'active')->count(),
    //         'low_stock'        => Product::where('stock_quantity', '<=', 5)->count(),

    //         'sales_chart'      => Checkout::selectRaw('MONTH(created_at) as month, SUM(total_amount) as revenue')
    //                                 ->where('status', 'completed')
    //                                 ->whereYear('created_at', now()->year)
    //                                 ->groupBy('month')
    //                                 ->orderBy('month')
    //                                 ->pluck('revenue', 'month'),

    //         'geo_marketing' => UserAddress::selectRaw('city, COUNT(*) as total')
    //                                 ->groupBy('city')
    //                                 ->orderBy('total')
    //                                 ->take(5)
    //                                 ->pluck('total','city')


    //     ]);
    // }

    public function allDashboard()
    {
        return response()->json([
            'accounts' => $this->accounts(),
            'orders'   => $this->orders(),
            'sales'    => $this->sales(),
            'traffic'  => $this->traffic(),
            'contacts' => $this->contacts(),
            'products' => $this->products(),
            'notifications' => $this->notifications(),
            'views'=> $this->views(),
        ]);
    }
    private function accounts(): array
    {
        return Cache::remember('dashboard.accounts', now()->addMinutes(10), function () {
            $stats = Account::selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN email_verified_at IS NOT NULL THEN 1 ELSE 0 END) as verified,
                SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as new_today,
                SUM(CASE WHEN MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW()) THEN 1 ELSE 0 END) as new_this_month
            ")->first();

            return [
                    'total'          => $stats->total,
                    'verified'       => $stats->verified,       // email verified users
                    'unverified'     => $stats->total - $stats->verified, // not yet verified
                    'new_today'      => $stats->new_today,
                    'new_this_month' => $stats->new_this_month,

                    'new_per_month'  => Account::selectRaw('MONTH(created_at) as month, COUNT(*) as total')
                                            ->whereYear('created_at', now()->year)
                                            ->groupBy('month')
                                            ->orderBy('month')
                                            ->pluck('total', 'month'),

                    'recent'         => Account::latest()
                                            ->take(6)
                                            ->get(['id', 'first_name', 'last_name', 'email', 'created_at', 'email_verified_at']),
            ];
        });
    }

private function orders(): array
{
    return Cache::remember('dashboard.orders', now()->addMinutes(10), function () {

        $all = Checkout::selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today,
            SUM(CASE WHEN paid_at IS NOT NULL THEN 1 ELSE 0 END) as paid,
            SUM(CASE WHEN paid_at IS NULL THEN 1 ELSE 0 END) as unpaid
        ")->first();

        $statusStats = DB::table('deliveries')->selectRaw("
            SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
            SUM(CASE WHEN status = 'ready'      THEN 1 ELSE 0 END) as ready,
            SUM(CASE WHEN status = 'on_the_way' THEN 1 ELSE 0 END) as on_the_way,
            SUM(CASE WHEN status = 'delivered'  THEN 1 ELSE 0 END) as delivered
        ")->first();

        $weekly = Checkout::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN paid_at IS NOT NULL THEN 1 ELSE 0 END) as paid,
                SUM(CASE WHEN paid_at IS NULL THEN 1 ELSE 0 END) as unpaid
            ")->first();

        return [
            'total'         => $all->total,
            'today'         => $all->today,
            'paid'          => $all->paid,
            'unpaid'        => $all->unpaid,
            'processing'    => $statusStats->processing,
            'ready'         => $statusStats->ready,
            'on_the_way'    => $statusStats->on_the_way,
            'delivered'     => $statusStats->delivered,
            'weekly_total'  => $weekly->total,
            'weekly_paid'   => $weekly->paid,
            'weekly_unpaid' => $weekly->unpaid,
                'recent' => Checkout::select(
                        'checkouts.checkout_id',
                        'checkouts.payment_method',
                        'checkouts.paid_amount',
                        'checkouts.paid_at',
                        'checkouts.created_at',
                        'accounts.first_name',
                        'accounts.last_name',
                        'accounts.email'
                )
                ->join('accounts', 'accounts.id', '=', 'checkouts.user_id')
                ->latest('checkouts.created_at')
                ->take(6)
                ->get(),
        ];

    });
}

        private function sales(): array{
            return Cache::remember('dashboard.sales', now()->addMinutes(15), function () {
            $revenue = Checkout::selectRaw("
                SUM(paid_amount) as total,
                SUM(CASE WHEN MONTH(created_at) = MONTH(NOW()) AND YEAR(created_at) = YEAR(NOW()) THEN paid_amount ELSE 0 END) as this_month,
                SUM(CASE WHEN DATE(created_at) = CURDATE() THEN paid_amount ELSE 0 END) as today
            ")->first();

            return [
                'total'              => $revenue->total,
                'this_month'         => $revenue->this_month,
                'today'              => $revenue->today,
                'monthly_chart'      => Checkout::selectRaw('MONTH(created_at) as month, SUM(paid_amount) as revenue')
                                            ->whereYear('created_at', now()->year)
                                            ->groupBy('month')->orderBy('month')
                                            ->pluck('revenue', 'month'),
                'monthly_chart_prev' => Checkout::selectRaw('MONTH(created_at) as month, SUM(paid_amount) as revenue')
                                            ->whereYear('created_at', now()->year - 1)
                                            ->groupBy('month')->orderBy('month')
                                            ->pluck('revenue', 'month'),
            ];
        });
    }

        private function traffic(): array {
    return Cache::remember('dashboard.traffic', now()->addMinutes(15), function () {
        return [
            // users by address (pwede panatilihin, galing sa user_addresses)
            'users_by_address'   => UserAddress::selectRaw('city, COUNT(*) as total')
                                        ->whereNotNull('city')
                                        ->where('city', '!=', '')
                                        ->groupBy('city')
                                        ->orderByDesc('total')
                                        ->take(10)
                                        ->pluck('total', 'city'),

            // orders by city (galing na sa checkouts.delivery_address)
            'orders_by_address'  => DB::table('checkouts')
                                        ->selectRaw("
                                            JSON_UNQUOTE(JSON_EXTRACT(delivery_address, '$.city')) as city,
                                            COUNT(*) as total
                                        ")
                                        ->whereRaw("JSON_EXTRACT(delivery_address, '$.city') IS NOT NULL")
                                        ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(delivery_address, '$.city')) != ''")
                                        ->groupByRaw("JSON_UNQUOTE(JSON_EXTRACT(delivery_address, '$.city'))")
                                        ->orderByDesc('total')
                                        ->take(10)
                                        ->pluck('total', 'city'),

            // ── PANGUNAHING BINAGO — revenue by city galing sa checkouts ──
            'revenue_by_address' => DB::table('checkouts')
                                        ->selectRaw("
                                            JSON_UNQUOTE(JSON_EXTRACT(delivery_address, '$.city')) as city,
                                            SUM(paid_amount) as revenue
                                        ")
                                        ->whereRaw("JSON_EXTRACT(delivery_address, '$.city') IS NOT NULL")
                                        ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(delivery_address, '$.city')) != ''")
                                        ->groupByRaw("JSON_UNQUOTE(JSON_EXTRACT(delivery_address, '$.city'))")
                                        ->orderByDesc('revenue')
                                        ->take(10)
                                        ->pluck('revenue', 'city'),

            // users by company (unchanged)
            'by_company'         => UserAddress::selectRaw('company_name, COUNT(*) as total')
                                        ->whereNotNull('company_name')
                                        ->groupBy('company_name')
                                        ->orderByDesc('total')
                                        ->take(10)
                                        ->pluck('total', 'company_name'),
        ];
    });
}
        private function contacts(): array
    {
        return Cache::remember('dashboard.contacts', now()->addMinutes(5), function () {
            return [
                'total'   => Contact::count(),
                'today'   => Contact::whereDate('created_at', today())->count(),
                'pending' => Contact::where('status', 'pending')->count(),
                'read'    => Contact::where('status', 'read')->count(),
                'replied' => Contact::where('status', 'replied')->count(),

                'recent'  => Contact::latest()
                            ->take(6)
                            ->get([
                                'message_id',
                                'first_name',
                                'last_name',
                                'email',
                                'phone_number',
                                'message',
                                'status',
                                'created_at',
                            ]),
            ];
        });
    }
    private function products() : array{
        return Cache::remember('dashboard.products', now()->addMinutes(10), function () {
            return[
                'total' => Product::count(),
                'on_sale' => Product::where('isSale',true)->count(),
                'in_stock'  => Product::where('status', 'in_stock')->count(),
                'pre_order' => Product::where('status', 'pre_order')->count(),
                'pre_order' => Product::where('status', 'pre_order')->count(),
                'recent' =>Product::latest()
                                    -> take(6)
                                    -> get([
                                        'product_id',
                                        'product_name',
                                        'price',
                                        'product_stocks',
                                        'isSale',
                                        'created_at',
                                    ]),
            ];
        });
    }

    private function notifications() : array{
        return Cache::remember('dashboard.notifications', now()->addMinute(5), function () {

            return[
                'total' => DB::table('notifications')->count(),
                'unread' => DB::table('notifications')->where('is_read', false) -> count(),
                'recent' => DB::table('notifications')
                                    ->latest()
                                    ->take(5)
                                    ->get([
                                        'notification_id',
                                        'user_id',
                                        'type',
                                        'title',
                                        'message',
                                        'is_read',
                                        'created_at',
                                    ]),
            ];

        });
    }

    private function views(): array
    {
        return Cache::remember('dashboard.views', now()->addMinutes(5), function () {
            return [
                'total_views'   => DB::table('dashboards')->sum('views'),
                'total_visits'  => DB::table('dashboards')->sum('visits'),
                'today_views'   => DB::table('dashboards')->whereDate('created_at', today())->sum('views'),
                'today_visits'  => DB::table('dashboards')->whereDate('created_at', today())->sum('visits'),
                'views_chart'   => DB::table('dashboards')
                                        ->selectRaw('DATE(created_at) as date, SUM(views) as total')
                                        ->where('created_at', '>=', now()->subDays(30))
                                        ->groupBy('date')
                                        ->orderBy('date')
                                        ->pluck('total', 'date'),
            ];
        });
    }
}
