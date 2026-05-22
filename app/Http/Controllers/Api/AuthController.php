<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email'       => 'required|email',
            'password'    => 'required|string',
            'device_name' => 'sometimes|string|max:255',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->status !== 'active') {
            return response()->json(['message' => 'Your account is not active.'], 403);
        }

        if ($user->role === 'owner') {
            return response()->json(['message' => 'Owners must log in via the owner portal.'], 403);
        }

        $token = $user->createToken($request->device_name ?? 'employee-portal')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => new UserResource($user->load('employeeProfile')),
        ]);
    }

    public function me(Request $request)
    {
        return new UserResource($request->user()->load('employeeProfile'));
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }
}
