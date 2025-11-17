<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderDetail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $selectedMonth = request()->get('month', now()->month);

        $currentDate = Carbon::now();
        $currentMonth = $selectedMonth;
        $lastMonth = $currentDate->copy()->subMonth()->month;
        
$topProducts = Product::select(
        'products.productID',
        'products.productName',
        'products.productCode',
        DB::raw('SUM(order_details.orderQuantity) as total_sold')
    )
    ->join('product_details', 'products.productID', '=', 'product_details.prdID')
    ->join('order_details', 'product_details.id', '=', 'order_details.productDetailID')
    ->join('orders', 'order_details.orderID', '=', 'orders.orderID')
    ->whereMonth('orders.created_at', $currentMonth)
    ->whereYear('orders.created_at', $currentDate->year)
    ->where('orders.staID', 4)
    ->groupBy('products.productID', 'products.productName', 'products.productCode')
    ->orderByDesc('total_sold')
    ->with('firstImage')
    ->take(3)
    ->get();



        $recentOrders = Order::with(['customer', 'status'])
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        $currentRevenue = Order::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentDate->year)
            ->where('staID', 4)
            ->sum('totalPrice');

        $lastRevenue = Order::whereMonth('created_at', $lastMonth)
            ->whereYear('created_at', $currentDate->year)
            ->where('staID', 4)
            ->sum('totalPrice');

        $currentOrders = Order::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentDate->year)
            ->count();

        $lastOrders = Order::whereMonth('created_at', $lastMonth)
            ->whereYear('created_at', $currentDate->year)
            ->count();

        // 4. Sản phẩm
        $totalProducts = Product::count();
        $newProducts = Product::whereMonth('created_at', $currentMonth)->count();

        // 5. Khách hàng
        $totalCustomers = User::where('role', 'customer')->count();
        $newCustomers = User::where('role', 'customer')
            ->whereMonth('created_at', $currentMonth)
            ->count();

        $lastCustomers = User::where('role', 'customer')
            ->whereMonth('created_at', $lastMonth)
            ->count();

        $revenuePerDay = Order::select(
            DB::raw('DAY(created_at) as day'),
            DB::raw('SUM(totalPrice) as total')
        )
            ->whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentDate->year)
            ->where('staID', 4)
            ->groupBy(DB::raw('DAY(created_at)'))
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $daysInMonth = $currentDate->daysInMonth;
        $revenueLabels = [];
        $revenueData = [];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $label = str_pad($day, 2, '0', STR_PAD_LEFT);
            $revenueLabels[] = $label;
            $revenueData[] = $revenuePerDay[$day]->total ?? 0;
        }

        return view('AdminPage.Dashboard', [
            'currentRevenue' => $currentRevenue,
            'revenueChange' => $lastRevenue > 0 ? (($currentRevenue - $lastRevenue) / $lastRevenue) * 100 : 100,

            'currentOrders' => $currentOrders,
            'orderChange' => $lastOrders > 0 ? (($currentOrders - $lastOrders) / $lastOrders) * 100 : 100,

            'totalProducts' => $totalProducts,
            'newProducts' => $newProducts,

            'totalCustomers' => $totalCustomers,
            'customerChange' => $lastCustomers > 0 ? (($totalCustomers - $lastCustomers) / $lastCustomers) * 100 : 100,

            'topProducts' => $topProducts,
            'recentOrders' => $recentOrders,

            'revenueLabels' => $revenueLabels,
            'revenueData' => $revenueData,
        ]);
    }


}
