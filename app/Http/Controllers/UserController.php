<?php

namespace App\Http\Controllers;
use App\Http\Resources\UserResource;
use App\Models\User;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        return UserResource::collection(User::paginate($perPage));
    }

    public function show(string $id)
    {
        $user = User::findOrFail($id);
        return new UserResource($user->load('userDetail'));
    }

    public function me(Request $request)
    {
        return new UserResource($request->user()->load('userDetail'));
    }
}
