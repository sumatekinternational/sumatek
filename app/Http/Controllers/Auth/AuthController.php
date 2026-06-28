<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        $token = $user->createToken($credentials['device_name'] ?? 'api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $this->profile($user),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => __('auth.logged_out')]);
    }

    public function me(Request $request)
    {
        return response()->json($this->profile($request->user()));
    }

    /**
     * Build the user profile payload. Roles/permissions are tenant-scoped, so
     * we set the Spatie team context to the user's agency first — otherwise the
     * auth endpoints (which run outside the tenant middleware) would report an
     * empty set and the SPA would hide every permission-gated screen.
     */
    protected function profile(User $user): array
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($user->tenant_id);
        $user->unsetRelation('roles')->unsetRelation('permissions');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'locale' => $user->locale,
            'tenant_id' => $user->tenant_id,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ];
    }
}
