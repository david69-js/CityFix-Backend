<?php

namespace App\Http\Controllers;

use App\Models\InvitationCode;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InvitationCodeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(InvitationCode::with('role')->get());
    }

    /**
     * Redeem an invitation code for the authenticated user.
     */
    public function redeem(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $invitation = InvitationCode::where('code', $request->code)->first();

        if (!$invitation || !$invitation->isValid()) {
            return response()->json(['error' => 'Código de invitación inválido o expirado'], 422);
        }

        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        // Update user role
        $user->update([
            'role_id' => $invitation->role_id
        ]);

        // Increment usage
        $invitation->increment('used_count');

        return response()->json([
            'message' => 'Rol actualizado con éxito',
            'user' => $user->load('role')
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'nullable|string|unique:invitation_codes,code',
            'role_id' => 'required|exists:roles,id',
            'is_active' => 'boolean',
            'expires_at' => 'nullable|date',
            'max_uses' => 'nullable|integer|min:1',
        ]);

        // Generate a random code if not provided
        if (empty($validated['code'])) {
            $validated['code'] = strtoupper(Str::random(8));
        }

        $invitationCode = InvitationCode::create($validated);

        return response()->json([
            'message' => 'Invitation code created successfully',
            'data' => $invitationCode
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(InvitationCode $invitationCode)
    {
        return response()->json($invitationCode->load('role'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, InvitationCode $invitationCode)
    {
        $validated = $request->validate([
            'is_active' => 'boolean',
            'expires_at' => 'nullable|date',
            'max_uses' => 'nullable|integer|min:1',
        ]);

        $invitationCode->update($validated);

        return response()->json([
            'message' => 'Invitation code updated successfully',
            'data' => $invitationCode
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(InvitationCode $invitationCode)
    {
        $invitationCode->delete();

        return response()->json([
            'message' => 'Invitation code deleted successfully'
        ]);
    }
}
