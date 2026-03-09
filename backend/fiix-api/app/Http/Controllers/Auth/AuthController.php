<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Domain\Users\Enums\UserRole;
use App\Domain\Users\Enums\UserStatus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name'  => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', 'unique:users,email'],
            'password'   => ['required', 'string', 'min:8', 'confirmed'],
            'role'       => ['required', 'in:customer,technician'],
        ]);

        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name'  => $data['last_name'],
            'email'      => $data['email'],
            'password'   => Hash::make($data['password']),
            'role'       => $data['role'],
            // Technicians start pending, customers start active
            'status'     => $data['role'] === UserRole::TECHNICIAN->value
                ? UserStatus::PENDING_VERIFICATION->value
                : UserStatus::ACTIVE->value,
        ])

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'data' => [
                'token' => $token,
                'user'  => [
                    'id'         => $user->id,
                    'first_name' => $user->first_name,
                    'last_name'  => $user->last_name,
                    'email'      => $user->email,
                    'role'       => $user->role,
                    'status'     => $user->status,
                ],
            ],
        ], 201);
    }

    // -- Login --
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        //Invalid credentials - same message for both cases(this is for security)
        if($user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        // Blocked or suspended accounts cannot log in
        if($user->status === UserStatus::BLOCKED->value) {
            return response()->json([
                'error' => [
                    'code'    => 'account_blocked',
                    'message' => 'This account has been blocked. Please contact support.',
                    'details' => (object) [],
                ]
            ], 403);
        }

        // Revoke old tokens and issue a fresh one
        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'data' => [
                'token' => $token,
                'user'  => [
                    'id'         => $user->id,
                    'first_name' => $user->first_name,
                    'last_name'  => $user->last_name,
                    'email'      => $user->email,
                    'role'       => $user->role,
                    'status'     => $user->status,
                ],
            ],
        ], 200);
    }

    // -- Logout --
    public function logout(Request $request): JsonResponse
    {
        // Delete only the token used in this request
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'data' => [
                'message' => 'Logged out successfully.',
            ],
        ], 200);
    }
}