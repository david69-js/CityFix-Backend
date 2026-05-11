<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\InvitationCode;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Routing\Controller;
use Google\Client as Google_Client;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        // For Laravel 10 and below, $this->middleware works. For Laravel 11+, route middleware is preferred,
        // but $this->middleware still works if \Illuminate\Routing\Controller handles it, which it usually does.
        // It's safer to not use the middleware inside the constructor if we get an issue, but we'll try it.
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        $credentials = request(['email', 'password']);

        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    /**
     * Register a User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request) {
        $validated = $request->validate([
            'first_name' => 'required|string|between:2,100',
            'last_name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|min:6', // Ensure you send "password" field
            'invitation_code' => 'sometimes|nullable|string',
        ]);

        $defaultRole = \App\Models\Role::where('name', 'Citizen')->first();
        $roleId = $defaultRole ? $defaultRole->id : null; // Defaults to Citizen dynamically

        if (!empty($validated['invitation_code'])) {
            $invitation = InvitationCode::where('code', $validated['invitation_code'])->first();
            
            if (!$invitation || !$invitation->isValid()) {
                return response()->json(['error' => 'Código de invitación inválido o expirado'], 422);
            }

            $roleId = $invitation->role_id;
            
            // Increment usage
            $invitation->increment('used_count');
        }

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name'  => $validated['last_name'],
            'email'      => $validated['email'],
            'password'   => Hash::make($request->password),
            'role_id'    => $roleId
        ]);

        $token = auth('api')->login($user);

        return response()->json([
            'message' => 'User successfully registered',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60
        ], 201);
    }

    /**
     * Login or Register via Google ID Token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function loginWithGoogle(Request $request)
    {
        $request->validate([
            'id_token' => 'required|string',
        ]);

        // Verify the Google ID token
        $client = new Google_Client(['client_id' => config('services.google.client_id')]);
        $payload = $client->verifyIdToken($request->id_token);

        if (!$payload) {
            return response()->json(['error' => 'Token de Google inválido'], 401);
        }

        $googleId = $payload['sub'];
        $email = $payload['email'] ?? null;
        $firstName = $payload['given_name'] ?? '';
        $lastName = $payload['family_name'] ?? '';
        $avatar = $payload['picture'] ?? null;

        // Find user by google_id or email
        $user = User::where('google_id', $googleId)->first();

        if (!$user && $email) {
            $user = User::where('email', $email)->first();
        }

        if ($user) {
            // Update google_id if not set (user previously registered with email/password)
            if (!$user->google_id) {
                $user->update(['google_id' => $googleId]);
            }
            // Update avatar from Google if user doesn't have one
            if (!$user->avatar && $avatar) {
                $user->update(['avatar' => $avatar]);
            }
        } else {
            // Create new user with Citizen role
            $defaultRole = Role::where('name', 'Citizen')->first();

            $user = User::create([
                'first_name' => $firstName,
                'last_name'  => $lastName,
                'email'      => $email,
                'google_id'  => $googleId,
                'avatar'     => $avatar,
                'password'   => null, // No password for Google users
                'role_id'    => $defaultRole ? $defaultRole->id : 1,
            ]);
        }

        // Generate JWT token
        $token = auth('api')->login($user);

        return response()->json([
            'message'      => 'Login con Google exitoso.',
            'user'         => $user->load('role'),
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => auth('api')->factory()->getTTL() * 60,
            'is_new_user'  => $user->wasRecentlyCreated,
        ]);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(auth('api')->user());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth('api')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth('api')->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60
        ]);
    }
}
