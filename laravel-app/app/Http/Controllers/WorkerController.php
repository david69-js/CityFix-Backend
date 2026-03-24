<?php

namespace App\Http\Controllers;

use App\Models\Worker;
use Illuminate\Http\Request;

class WorkerController extends Controller
{
    public function index()
    {
        return response()->json(Worker::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // Add your validation rules
        ]);
        $worker = Worker::create($validated);
        return response()->json($worker, 201);
    }

    public function show(Worker $worker)
    {
        return response()->json($worker);
    }

    public function update(Request $request, Worker $worker)
    {
        $validated = $request->validate([
            // Add your validation rules
        ]);
        $worker->update($validated);
        return response()->json($worker);
    }

    public function destroy(Worker $worker)
    {
        $worker->delete();
        return response()->json(null, 204);
    }
}
