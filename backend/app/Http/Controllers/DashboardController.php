<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\User;
use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function statistics()
    {
        try {
            // Basic statistics with fallbacks
            $totalProducts = Product::count();
            $totalCustomers = User::where('role', 'user')->count();
            $totalEvents = Event::count();
            $totalTickets = Ticket::count();
            $totalOrders = Purchase::count();

            // Revenue calculations with fallbacks
            $totalRevenue = Purchase::where('status', 'completed')->sum('total') ?? 0;
            $monthlyRevenue = Purchase::where('status', 'completed')
                ->whereMonth('created_at', Carbon::now()->month)
                ->whereYear('created_at', Carbon::now()->year)
                ->sum('total') ?? 0;

            $lastMonthRevenue = Purchase::where('status', 'completed')
                ->whereMonth('created_at', Carbon::now()->subMonth()->month)
                ->whereYear('created_at', Carbon::now()->subMonth()->year)
                ->sum('total') ?? 0;

            // Growth calculations
            $revenueGrowth = $lastMonthRevenue > 0 
                ? (($monthlyRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100 
                : 0;

            $customerGrowth = $this->calculateGrowth(
                User::where('role', 'user')->whereMonth('created_at', Carbon::now()->subMonth())->count(),
                User::where('role', 'user')->whereMonth('created_at', Carbon::now())->count()
            );

            $productGrowth = $this->calculateGrowth(
                Product::whereMonth('created_at', Carbon::now()->subMonth())->count(),
                Product::whereMonth('created_at', Carbon::now())->count()
            );

            $orderGrowth = $this->calculateGrowth(
                Purchase::whereMonth('created_at', Carbon::now()->subMonth())->count(),
                Purchase::whereMonth('created_at', Carbon::now())->count()
            );

            return response()->json([
                'total_revenue' => $totalRevenue,
                'total_orders' => $totalOrders,
                'total_products' => $totalProducts,
                'total_customers' => $totalCustomers,
                'total_events' => $totalEvents,
                'total_tickets' => $totalTickets,
                'monthly_growth' => round($revenueGrowth, 1),
                'order_growth' => round($orderGrowth, 1),
                'product_growth' => round($productGrowth, 1),
                'customer_growth' => round($customerGrowth, 1),
                'monthly_revenue' => $monthlyRevenue,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch statistics',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function revenueChart()
    {
        try {
            $months = [];
            $revenueData = [];
            $ordersData = [];

            // Get last 12 months
            for ($i = 11; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i);
                $months[] = $date->format('M');
                
                $revenue = Purchase::where('status', 'completed')
                    ->whereMonth('created_at', $date->month)
                    ->whereYear('created_at', $date->year)
                    ->sum('total') ?? 0;
                    
                $orders = Purchase::whereMonth('created_at', $date->month)
                    ->whereYear('created_at', $date->year)
                    ->count();

                $revenueData[] = $revenue;
                $ordersData[] = $orders;
            }

            return response()->json([
                'labels' => $months,
                'revenue' => $revenueData,
                'orders' => $ordersData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch revenue chart data',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function orderStatusChart()
    {
        try {
            $statusCounts = Purchase::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            return response()->json([
                'pending' => $statusCounts['pending'] ?? 0,
                'processing' => $statusCounts['processing'] ?? 0,
                'shipped' => $statusCounts['shipped'] ?? 0,
                'completed' => $statusCounts['completed'] ?? 0,
                'cancelled' => $statusCounts['cancelled'] ?? 0,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch order status data',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function categoryChart()
    {
        try {
            // Simple approach - get categories with product counts
            $categoryData = DB::table('categories')
                ->leftJoin('subcategories', 'categories.id', '=', 'subcategories.category_id')
                ->leftJoin('products', 'subcategories.id', '=', 'products.subcategory_id')
                ->select('categories.name', DB::raw('count(products.id) as count'))
                ->groupBy('categories.id', 'categories.name')
                ->orderBy('count', 'desc')
                ->get();

            $labels = $categoryData->pluck('name')->toArray();
            $data = $categoryData->pluck('count')->toArray();

            return response()->json([
                'labels' => $labels,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch category data',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function topProducts()
    {
        try {
            // Simple approach - get products with their counts
            $topProducts = Product::select('name', 'slug')
                ->withCount('variants')
                ->orderBy('variants_count', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($product) {
                    return [
                        'name' => $product->name,
                        'slug' => $product->slug,
                        'total_sales' => $product->variants_count ?? 0,
                        'total_revenue' => 0 // Simplified for now
                    ];
                });

            return response()->json($topProducts);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch top products',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function recentOrders()
    {
        try {
            $recentOrders = Purchase::with(['user'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($purchase) {
                    return [
                        'id' => $purchase->id,
                        'customer' => $purchase->user->user_name ?? 'Guest',
                        'email' => $purchase->user->email ?? '',
                        'amount' => $purchase->total ?? 0,
                        'status' => $purchase->status ?? 'pending',
                        'date' => $purchase->created_at->format('Y-m-d'),
                        'items' => $purchase->items()->sum('quantity') ?? 0
                    ];
                });

            return response()->json($recentOrders);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch recent orders',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function calculateGrowth($oldValue, $newValue)
    {
        if ($oldValue == 0) {
            return $newValue > 0 ? 100 : 0;
        }
        
        return (($newValue - $oldValue) / $oldValue) * 100;
    }
}