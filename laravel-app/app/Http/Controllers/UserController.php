<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    private function imageDisk(): string
    {
        return env('FILESYSTEM_DISK', 'local') === 'r2' ? 'r2' : 'public';
    }

    public function index()
    {
        return response()->json(User::with('role')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|min:6',
            'phone' => 'nullable|string|max:20',
            'role_id' => 'required|exists:roles,id',
            'avatar' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('avatar')) {
            $validated['avatar'] = $request->file('avatar')->store('avatars', $this->imageDisk());
        }

        $validated['password'] = \Illuminate\Support\Facades\Hash::make($validated['password']);

        $user = User::create($validated);
        return response()->json($user->load('role'), 201);
    }

    public function show(User $user)
    {
        return response()->json($user);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'role_id' => 'sometimes|exists:roles,id',
            'first_name' => 'sometimes|string|max:100',
            'last_name' => 'sometimes|string|max:100',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'avatar' => 'nullable|image|max:2048',
            'password' => 'sometimes|string|min:6',
        ]);

        if ($request->hasFile('avatar')) {
            $validated['avatar'] = $request->file('avatar')->store('avatars', $this->imageDisk());
        }

        if (isset($validated['password'])) {
            $validated['password'] = \Illuminate\Support\Facades\Hash::make($validated['password']);
        }

        $user->update($validated);
        return response()->json($user->load('role'));
    }

    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(null, 204);
    }
    /**
     * Update the authenticated user's profile.
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:100',
            'last_name' => 'sometimes|string|max:100',
            'phone' => 'nullable|string|max:20',
            'avatar' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                \Illuminate\Support\Facades\Storage::disk($this->imageDisk())->delete($user->avatar);
            }
            $validated['avatar'] = $request->file('avatar')->store('avatars', $this->imageDisk());
        }

        $user->update($validated);

        return response()->json([
            'message' => 'Perfil actualizado con éxito',
            'user' => $user->load('role')
        ]);
    }

    public function updateFcmToken(Request $request)
    {
        $validated = $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $request->user()->update([
            'fcm_token' => $validated['fcm_token']
        ]);

        return response()->json(['message' => 'FCM token updated successfully']);
    }

    /**
     * Toggle user active/archived status.
     * PATCH /api/admin/users/{user}/toggle-active
     */
    public function toggleActive(Request $request, User $user)
    {
        // Prevenir desactivar a otro administrador
        if ($user->hasRole('Admin') && $user->id !== auth('api')->id()) {
            return response()->json([
                'message' => 'No puedes desactivar a otro administrador.'
            ], 403);
        }

        $newState = !$user->is_active;
        $user->update(['is_active' => $newState]);

        return response()->json([
            'message' => $newState ? 'Usuario activado correctamente.' : 'Usuario archivado correctamente.',
            'user'    => $user->load('role'),
            'is_active' => $newState,
        ]);
    }
}
