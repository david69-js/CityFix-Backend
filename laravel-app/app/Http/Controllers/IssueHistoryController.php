<?php

namespace App\Http\Controllers;

use App\Models\Issue;
use App\Models\IssueHistory;
use Illuminate\Http\Request;
use Carbon\Carbon;

class IssueHistoryController extends Controller
{
    public function index()
    {
        return response()->json(IssueHistory::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // Add your validation rules
        ]);
        $issueHistory = IssueHistory::create($validated);
        return response()->json($issueHistory, 201);
    }

    public function show(IssueHistory $issueHistory)
    {
        return response()->json($issueHistory);
    }

    public function update(Request $request, IssueHistory $issueHistory)
    {
        $validated = $request->validate([
            // Add your validation rules
        ]);
        $issueHistory->update($validated);
        return response()->json($issueHistory);
    }

    public function destroy(IssueHistory $issueHistory)
    {
        $issueHistory->delete();
        return response()->json(null, 204);
    }

    public function historyLogs(Issue $issue)
    {
        $history = $issue->history()
            ->with(['status', 'changedBy:id,first_name,last_name'])
            ->orderBy('changed_at', 'asc')
            ->get();

        $logs = $history->map(function ($item, $index) use ($issue, $history) {
            $prevTime = ($index === 0) ? $issue->created_at : $history[$index - 1]->changed_at;
            $duration = $item->changed_at->diffForHumans($prevTime, [
                'syntax' => Carbon::DIFF_ABSOLUTE,
                'parts' => 2,
            ]);

            return [
                'status' => $item->status->name,
                'changed_by' => $item->changedBy ? ($item->changedBy->first_name . ' ' . $item->changedBy->last_name) : 'Sistema',
                'changed_at' => $item->changed_at->toDateTimeString(),
                'time_since_last_change' => $duration,
            ];
        });

        // Calculo de tiempo total resolución si existe el estado "Resuelto"
        $resolvedAt = $history->where('status.name', 'Resuelto')->first()?->changed_at;
        $totalResolutionTime = $resolvedAt ? $resolvedAt->diffForHumans($issue->created_at, [
            'syntax' => Carbon::DIFF_ABSOLUTE,
            'parts' => 3,
        ]) : null;

        return response()->json([
            'issue_id' => $issue->id,
            'created_at' => $issue->created_at->toDateTimeString(),
            'total_resolution_time' => $totalResolutionTime,
            'history' => $logs,
        ]);
    }
}
