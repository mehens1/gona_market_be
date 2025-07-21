<?php

namespace App\Http\Controllers;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use App\Models\Order;

class OrderController extends Controller
{
    use ApiResponseTrait;

    public function index()
    {
        try {
            // Logic to retrieve orders
            $orders = Order::all();
            return $this->successResponse($orders, 'Orders retrieved successfully');
        } catch (\Exception $e) {
            \Log::error('Error retrieving orders: ' . $e->getMessage());
            return $this->errorResponse('Failed to retrieve orders', 500);
        }
    }

    public function myOrders()
    {
        try {
            $user = auth()->user();
            $orders = Order::where('user_id', $user->id)->get();
            return $this->successResponse($orders, 'Your Orders retrieved successfully');
        } catch (\Exception $e) {
            \Log::error('Error retrieving orders: ' . $e->getMessage());
            return $this->errorResponse('Failed to retrieve orders', 500);
        }
    }
    public function show($id)
    {
        try {
            $order = Order::with('items.product')->findOrFail($id);
            return $this->successResponse($order, 'Order retrieved successfully');
        } catch (\Exception $e) {
            \Log::error('Error retrieving order: ' . $e->getMessage());
            return $this->errorResponse('Failed to retrieve order', 500);
        }
    }
}
