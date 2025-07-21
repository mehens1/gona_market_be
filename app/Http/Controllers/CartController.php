<?php

namespace App\Http\Controllers;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\Cart;

use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $perPage = $request->input('per_page') ?? 10;

        $query = Cart::where('user_id', $user->id);

        $query->paginate($perPage);

        // $cartProducts = $query->with('products')->get();
        $cartProducts = $query->get();

        return $cartProducts;




        $perPage = $request->input('per_page', 10);
        return UserResource::collection(User::paginate($perPage));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        return $user;
        return $request->all();
    }
}
