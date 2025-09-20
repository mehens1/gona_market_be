<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);

        $users = User::with('userDetail')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return UserResource::collection($users);
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

    public function updateUserStatus(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'is_active' => 'required|boolean',
        ]);

        try {
            $user = User::findOrFail($request->user_id);
            $user->is_active = $request->is_active;
            $user->save();

            return response()->json(['message' => 'User status updated successfully', 'status' => true], 200);
            // return new UserResource($request->user()->load('userDetail'));
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Failed to update user status', 'status' => false, 'error' => $th->getMessage()], 500);
        }
        // $request->user()->is_active = $request->is_active;
        // $request->user()->save();




    }
}
