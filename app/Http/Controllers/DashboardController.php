<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'admin') {
            return $this->adminDashboard();
        }

        if ($user->role === 'supplier') {
            return response()->json([
                'products_count' => Product::where('supplier_id', $user->id)->count(),
                'orders_incoming' => Order::where('supplier_id', $user->id)->where('status', '!=', 'cancelled')->count(),
                'pending_orders' => Order::where('supplier_id', $user->id)->where('status', 'pending')->count(),
                'shipped_orders' => Order::where('supplier_id', $user->id)->where('delivery_status', 'shipped')->count(),
                'sales_total' => Order::where('supplier_id', $user->id)->where('payment_status', 'paid')->sum('total'),
            ]);
        }

        return response()->json([
            'orders_total' => Order::where('buyer_id', $user->id)->count(),
            'pending_orders' => Order::where('buyer_id', $user->id)->where('status', 'pending')->count(),
            'processed_orders' => Order::where('buyer_id', $user->id)->where('status', 'processing')->count(),
            'shipped_orders' => Order::where('buyer_id', $user->id)->where('delivery_status', 'shipped')->count(),
            'completed_orders' => Order::where('buyer_id', $user->id)->where('status', 'completed')->count(),
            'total_spending' => Order::where('buyer_id', $user->id)->where('payment_status', 'paid')->sum('total'),
        ]);
    }

    public function adminDashboard()
    {
        return response()->json([
            'users' => User::count(),
            'buyers' => User::where('role', 'buyer')->count(),
            'suppliers' => User::where('role', 'supplier')->count(),
            'products_active' => Product::where('is_active', true)->count(),
            'orders' => Order::count(),
            'transaction_value' => Order::sum('total'),
            'complaints_open' => Complaint::where('status', 'open')->count(),
        ]);
    }
}
