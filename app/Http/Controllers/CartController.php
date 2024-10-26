<?php

namespace App\Http\Controllers;
use App\Http\Resources\UserResource;
use App\Models\User;

use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request)
    {
        return 'cart info here';
        $perPage = $request->input('per_page', 10);
        return UserResource::collection(User::paginate($perPage));

    }

    public function store(Request $request)
    {
        return 'stire';
    }
}
