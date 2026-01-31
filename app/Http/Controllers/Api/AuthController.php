<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'device_name' => ['required', 'string', 'max:255'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // Assign default viewer role
        $user->assignRole('viewer');

        $token = $user->createToken(
            $validated['device_name'],
            $this->getDefaultAbilities('viewer')
        );

        return response()->json([
            'user' => $user,
            'token' => $token->plainTextToken,
        ], 201);
    }

    /**
     * Login and get an API token.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:255'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Get abilities based on user's roles
        $abilities = $this->getAbilitiesForUser($user);

        $token = $user->createToken($validated['device_name'], $abilities);

        return response()->json([
            'user' => $user->load('roles'),
            'token' => $token->plainTextToken,
            'abilities' => $abilities,
        ]);
    }

    /**
     * Logout and revoke the current token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Successfully logged out',
        ]);
    }

    /**
     * Logout from all devices by revoking all tokens.
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Successfully logged out from all devices',
        ]);
    }

    /**
     * Get the current user's profile.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['roles', 'permissions', 'currentCompany', 'companies']);

        return response()->json([
            'user' => $user,
            'abilities' => $request->user()->currentAccessToken()->abilities ?? ['*'],
        ]);
    }

    /**
     * Refresh the current token.
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentToken = $user->currentAccessToken();
        $tokenName = $currentToken->name;
        $abilities = $this->getAbilitiesForUser($user);

        // Delete current token
        $currentToken->delete();

        // Create new token
        $newToken = $user->createToken($tokenName, $abilities);

        return response()->json([
            'token' => $newToken->plainTextToken,
            'abilities' => $abilities,
        ]);
    }

    /**
     * Get default abilities for a role.
     */
    protected function getDefaultAbilities(string $role): array
    {
        return match ($role) {
            'owner' => ['*'],
            'admin' => [
                'accounts:read', 'accounts:create', 'accounts:update', 'accounts:delete',
                'transactions:read', 'transactions:create', 'transactions:update', 'transactions:delete', 'transactions:post',
                'reports:read', 'reports:export',
                'companies:read', 'companies:update',
                'users:read', 'users:manage',
            ],
            'accountant' => [
                'accounts:read', 'accounts:create', 'accounts:update',
                'transactions:read', 'transactions:create', 'transactions:update', 'transactions:post',
                'reports:read', 'reports:export',
                'companies:read',
            ],
            'bookkeeper' => [
                'accounts:read',
                'transactions:read', 'transactions:create', 'transactions:update',
                'reports:read',
                'companies:read',
            ],
            'viewer' => [
                'accounts:read',
                'transactions:read',
                'reports:read',
                'companies:read',
            ],
            default => ['accounts:read', 'transactions:read', 'reports:read'],
        };
    }

    /**
     * Get abilities based on user's roles.
     */
    protected function getAbilitiesForUser(User $user): array
    {
        // Get the highest role and return corresponding abilities
        $roles = $user->getRoleNames()->toArray();

        // Priority: owner > admin > accountant > bookkeeper > viewer
        if (in_array('owner', $roles)) {
            return $this->getDefaultAbilities('owner');
        }
        if (in_array('admin', $roles)) {
            return $this->getDefaultAbilities('admin');
        }
        if (in_array('accountant', $roles)) {
            return $this->getDefaultAbilities('accountant');
        }
        if (in_array('bookkeeper', $roles)) {
            return $this->getDefaultAbilities('bookkeeper');
        }
        if (in_array('viewer', $roles)) {
            return $this->getDefaultAbilities('viewer');
        }

        return $this->getDefaultAbilities('viewer');
    }
}
