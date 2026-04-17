<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        return response()->json(User::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // Add your validation rules
        ]);
        $user = User::create($validated);
        return response()->json($user, 201);
    }

    public function show(User $user)
    {
        return response()->json($user);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:100',
            'last_name' => 'sometimes|string|max:100',
            'email' => 'sometimes|string|email|max:100|unique:users,email,' . $user->id,
            'role_id' => 'sometimes|exists:roles,id',
            'phone' => 'sometimes|string|max:20',
        ]);

        $user->update($validated);
        
        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user->load('role')
        ]);
    }

    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(null, 204);
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
}
