<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class MobileAuthController extends Controller
{
    /**
     * Handle a mobile API login request.
     *
     * @throws ValidationException
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()
            ->where('email', $validated['email'])
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('mobile-token')->plainTextToken;

        return $this->successResponse('Login success', [
            'user' => UserResource::make($user->loadMissing('roles.permissions'))->resolve(),
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Handle a mobile API registration request.
     *
     * @throws ValidationException
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        $user->assignRole(Role::findOrCreate('User', 'web'));

        $token = $user->createToken('mobile-token')->plainTextToken;

        return $this->successResponse('Register success', [
            'user' => UserResource::make($user->loadMissing('roles.permissions'))->resolve(),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    /**
     * Return the authenticated mobile API user.
     */
    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return $this->successResponse('Success', [
            'user' => UserResource::make($user->loadMissing('roles.permissions'))->resolve(),
        ]);
    }

    /**
     * Revoke the current mobile API access token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return $this->successResponse('Logout success', (object) []);
    }

    /**
     * Build a successful mobile API response.
     *
     * @param  array<string, mixed>|object  $data
     */
    private function successResponse(string $message, array|object $data, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }
}
