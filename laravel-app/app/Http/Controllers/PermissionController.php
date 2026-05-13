<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function index()
    {
        return response()->json(Permission::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:50|unique:permissions,name',
            'description' => 'nullable|string|max:255',
        ]);
        $permission = Permission::create($validated);
        return response()->json($permission, 201);
    }

    public function show(Permission $permission)
    {
        return response()->json($permission);
    }

    public function update(Request $request, Permission $permission)
    {
        $validated = $request->validate([
            'name'        => 'sometimes|string|max:50|unique:permissions,name,' . $permission->id,
            'description' => 'nullable|string|max:255',
        ]);
        $permission->update($validated);
        return response()->json($permission);
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();
        return response()->json(null, 204);
    }
}
