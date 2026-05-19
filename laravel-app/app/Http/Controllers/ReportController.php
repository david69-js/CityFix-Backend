<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\AssignmentStatus;
use App\Models\Category;
use App\Models\Comment;
use App\Models\Issue;
use App\Models\IssueStatus;
use App\Models\Upvote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    private function getDateRange(Request $request): array
    {
        $from = $request->query('from', now()->subMonth()->toDateString());
        $to = $request->query('to', now()->toDateString());
        return [$from, $to . ' 23:59:59'];
    }

    public function summary(Request $request)
    {
        [$from, $to] = $this->getDateRange($request);

        $issuesQuery = Issue::whereBetween('created_at', [$from, $to]);
        $totalIssues = (clone $issuesQuery)->count();

        $statusCounts = (clone $issuesQuery)
            ->selectRaw('status_id, count(*) as total')
            ->groupBy('status_id')
            ->pluck('total', 'status_id');

        $byStatus = IssueStatus::orderBy('sort_order')->get()->map(function ($status) use ($statusCounts) {
            return [
                'status' => $status->name,
                'total' => (int) $statusCounts->get($status->id, 0),
            ];
        });

        $categoryCounts = (clone $issuesQuery)
            ->selectRaw('category_id, count(*) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id');

        $byCategory = Category::orderBy('name')->get()->map(function ($category) use ($categoryCounts) {
            return [
                'category' => $category->name,
                'total' => (int) $categoryCounts->get($category->id, 0),
            ];
        });

        $issueIds = (clone $issuesQuery)->pluck('id');
        $totalUpvotes = Upvote::whereIn('issue_id', $issueIds)->count();
        $totalComments = Comment::whereIn('issue_id', $issueIds)
            ->whereBetween('created_at', [$from, $to])
            ->count();
        $totalWorkers = Assignment::whereIn('issue_id', $issueIds)
            ->distinct('worker_id')
            ->count('worker_id');

        $resolvedStatus = IssueStatus::where('name', 'Resuelto')->first();
        $avgSeconds = null;
        if ($resolvedStatus) {
            $avgSeconds = DB::table('issue_history')
                ->join('issues', 'issue_history.issue_id', '=', 'issues.id')
                ->where('issue_history.status_id', $resolvedStatus->id)
                ->whereBetween('issues.created_at', [$from, $to])
                ->selectRaw('AVG(EXTRACT(EPOCH FROM issue_history.changed_at - issues.created_at)) as avg')
                ->value('avg');
        }

        return response()->json([
            'from' => $from,
            'to' => $to,
            'total_issues' => $totalIssues,
            'by_status' => $byStatus,
            'by_category' => $byCategory,
            'total_upvotes' => $totalUpvotes,
            'total_comments' => $totalComments,
            'total_workers_assigned' => $totalWorkers,
            'avg_resolution_time_hours' => $avgSeconds ? round($avgSeconds / 3600, 2) : null,
        ]);
    }

    public function byCategory(Request $request)
    {
        [$from, $to] = $this->getDateRange($request);
        $categoryId = $request->query('category_id');

        $query = Issue::whereBetween('created_at', [$from, $to]);
        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $data = (clone $query)
            ->selectRaw('category_id, status_id, count(*) as total')
            ->groupBy('category_id', 'status_id')
            ->with('category', 'status')
            ->get()
            ->groupBy('category_id');

        $resolvedStatus = IssueStatus::where('name', 'Resuelto')->first();

        $result = [];
        foreach ($data as $catId => $rows) {
            $category = $rows->first()->category;
            $total = (int) $rows->sum('total');

            $byStatus = $rows->map(function ($row) {
                return ['status' => $row->status->name, 'total' => (int) $row->total];
            });

            $resolvedCount = 0;
            $avgSeconds = null;
            if ($resolvedStatus) {
                $resolvedCount = (clone $query)->where('category_id', $catId)->where('status_id', $resolvedStatus->id)->count();
                $avgSeconds = DB::table('issue_history')
                    ->join('issues', 'issue_history.issue_id', '=', 'issues.id')
                    ->where('issues.category_id', $catId)
                    ->where('issue_history.status_id', $resolvedStatus->id)
                    ->whereBetween('issues.created_at', [$from, $to])
->selectRaw('AVG(EXTRACT(EPOCH FROM issue_history.changed_at - issues.created_at)) as avg')
                    ->value('avg');
            }

            $result[] = [
                'category' => $category->name,
                'total' => $total,
                'by_status' => $byStatus,
                'resolved_count' => $resolvedCount,
                'avg_resolution_time_hours' => $avgSeconds ? round($avgSeconds / 3600, 2) : null,
            ];
        }

        return response()->json(['from' => $from, 'to' => $to, 'data' => $result]);
    }

    public function byWorker(Request $request)
    {
        [$from, $to] = $this->getDateRange($request);
        $workerId = $request->query('worker_id');

        $baseQuery = Assignment::whereBetween('assigned_at', [$from, $to]);
        if ($workerId) {
            $baseQuery->where('worker_id', $workerId);
        }

        $workers = (clone $baseQuery)
            ->selectRaw('worker_id, count(*) as total_assigned')
            ->groupBy('worker_id')
            ->with('worker:id,first_name,last_name,email')
            ->get();

        $completedStatus = AssignmentStatus::where('name', 'Completed')->first();
        $resolvedStatus = IssueStatus::where('name', 'Resuelto')->first();

        $result = $workers->map(function ($item) use ($from, $to, $completedStatus, $resolvedStatus) {
            $worker = $item->worker;

            $completedCount = 0;
            if ($completedStatus) {
                $completedCount = Assignment::where('worker_id', $worker->id)
                    ->where('status_id', $completedStatus->id)
                    ->whereBetween('assigned_at', [$from, $to])
                    ->count();
            }

            $resolvedCount = 0;
            $avgSeconds = null;
            if ($resolvedStatus) {
                $ids = Assignment::where('worker_id', $worker->id)
                    ->whereBetween('assigned_at', [$from, $to])
                    ->pluck('issue_id');

                $resolvedCount = Issue::whereIn('id', $ids)->where('status_id', $resolvedStatus->id)->count();

                $avgSeconds = DB::table('assignments')
                    ->join('issue_history', function ($join) use ($resolvedStatus) {
                        $join->on('assignments.issue_id', '=', 'issue_history.issue_id')
                            ->where('issue_history.status_id', '=', $resolvedStatus->id);
                    })
                    ->where('assignments.worker_id', $worker->id)
                    ->whereBetween('assignments.assigned_at', [$from, $to])
                    ->selectRaw('AVG(EXTRACT(EPOCH FROM issue_history.changed_at - assignments.assigned_at)) as avg')
                    ->value('avg');
            }

            $categories = Assignment::where('worker_id', $worker->id)
                ->whereBetween('assigned_at', [$from, $to])
                ->with('issue.category')
                ->get()
                ->groupBy('issue.category.name')
                ->map(function ($items, $catName) {
                    return ['category' => $catName, 'total' => $items->count()];
                })
                ->values();

            return [
                'worker' => [
                    'id' => $worker->id,
                    'first_name' => $worker->first_name,
                    'last_name' => $worker->last_name,
                    'email' => $worker->email,
                ],
                'total_assigned' => (int) $item->total_assigned,
                'completed_count' => $completedCount,
                'issues_resolved' => $resolvedCount,
                'categories_worked' => $categories,
                'avg_completion_time_hours' => $avgSeconds ? round($avgSeconds / 3600, 2) : null,
            ];
        });

        return response()->json(['from' => $from, 'to' => $to, 'data' => $result]);
    }

    public function byDate(Request $request)
    {
        [$from, $to] = $this->getDateRange($request);
        $groupBy = $request->query('group_by', 'day');

        $driver = DB::connection()->getDriverName();
        $isPgsql = $driver === 'pgsql';

        [$dateFn, $dateFmt] = match ($groupBy) {
            'week' => $isPgsql
                ? ["TO_CHAR(%s, 'IYYY-\"W\"IW')", "TO_CHAR(%s, 'IYYY-\"W\"IW')"]
                : ["DATE_FORMAT(%s, '%%x-W%%v')", "DATE_FORMAT(%s, '%%x-W%%v')"],
            'month' => $isPgsql
                ? ["TO_CHAR(%s, 'YYYY-MM')", "TO_CHAR(%s, 'YYYY-MM')"]
                : ["DATE_FORMAT(%s, '%%Y-%%m')", "DATE_FORMAT(%s, '%%Y-%%m')"],
            default => $isPgsql
                ? ["TO_CHAR(%s, 'YYYY-MM-DD')", "TO_CHAR(%s, 'YYYY-MM-DD')"]
                : ["DATE_FORMAT(%s, '%%Y-%%m-%%d')", "DATE_FORMAT(%s, '%%Y-%%m-%%d')"],
        };

        $created = Issue::whereBetween('created_at', [$from, $to])
            ->selectRaw(sprintf($dateFn, 'created_at') . " as period, count(*) as total")
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        $resolvedStatus = IssueStatus::where('name', 'Resuelto')->first();
        $resolved = collect();
        if ($resolvedStatus) {
            $resolved = DB::table('issue_history')
                ->join('issues', 'issue_history.issue_id', '=', 'issues.id')
                ->where('issue_history.status_id', $resolvedStatus->id)
                ->whereBetween('issues.created_at', [$from, $to])
                ->selectRaw(sprintf($dateFmt, 'issue_history.changed_at') . " as period, count(*) as total")
                ->groupBy('period')
                ->orderBy('period')
                ->get();
        }

        return response()->json([
            'from' => $from,
            'to' => $to,
            'group_by' => $groupBy,
            'created' => $created,
            'resolved' => $resolved,
        ]);
    }

    public function resolutionTimes(Request $request)
    {
        [$from, $to] = $this->getDateRange($request);
        $categoryId = $request->query('category_id');

        $resolvedStatus = IssueStatus::where('name', 'Resuelto')->first();

        if (!$resolvedStatus) {
            return response()->json(['message' => 'No se encontró el estado "Resuelto"'], 404);
        }

        $query = DB::table('issue_history')
            ->join('issues', 'issue_history.issue_id', '=', 'issues.id')
            ->where('issue_history.status_id', $resolvedStatus->id)
            ->whereBetween('issues.created_at', [$from, $to]);

        if ($categoryId) {
            $query->where('issues.category_id', $categoryId);
        }

        $stats = (clone $query)
            ->selectRaw('
                COUNT(*) as issues_resolved,
                AVG(EXTRACT(EPOCH FROM issue_history.changed_at - issues.created_at)) as avg_seconds,
                MIN(EXTRACT(EPOCH FROM issue_history.changed_at - issues.created_at)) as min_seconds,
                MAX(EXTRACT(EPOCH FROM issue_history.changed_at - issues.created_at)) as max_seconds
            ')
            ->first();

        $byWorker = DB::table('assignments')
            ->join('issue_history', function ($join) use ($resolvedStatus) {
                $join->on('assignments.issue_id', '=', 'issue_history.issue_id')
                    ->where('issue_history.status_id', '=', $resolvedStatus->id);
            })
            ->join('issues', 'assignments.issue_id', '=', 'issues.id')
            ->join('users', 'assignments.worker_id', '=', 'users.id')
            ->whereBetween('issues.created_at', [$from, $to])
            ->selectRaw('
                users.id, users.first_name, users.last_name, users.email,
                COUNT(*) as issues_resolved,
                AVG(EXTRACT(EPOCH FROM issue_history.changed_at - issues.created_at)) as avg_seconds
            ')
            ->groupBy('users.id', 'users.first_name', 'users.last_name', 'users.email')
            ->get()
            ->map(function ($item) {
                return [
                    'worker' => [
                        'id' => $item->id,
                        'first_name' => $item->first_name,
                        'last_name' => $item->last_name,
                        'email' => $item->email,
                    ],
                    'issues_resolved' => (int) $item->issues_resolved,
                    'avg_resolution_time_hours' => $item->avg_seconds ? round($item->avg_seconds / 3600, 2) : null,
                ];
            });

        return response()->json([
            'from' => $from,
            'to' => $to,
            'issues_resolved' => (int) $stats->issues_resolved,
            'avg_hours' => $stats->avg_seconds ? round($stats->avg_seconds / 3600, 2) : null,
            'min_hours' => $stats->min_seconds ? round($stats->min_seconds / 3600, 2) : null,
            'max_hours' => $stats->max_seconds ? round($stats->max_seconds / 3600, 2) : null,
            'by_worker' => $byWorker,
        ]);
    }

    public function details(Request $request)
    {
        [$from, $to] = $this->getDateRange($request);

        $query = Issue::with([
            'user:id,first_name,last_name,email',
            'category',
            'status',
            'history',
            'assignments.worker:id,first_name,last_name',
        ])->withCount(['upvotes', 'comments'])
        ->whereBetween('created_at', [$from, $to]);

        if ($request->has('status_id')) {
            $query->where('status_id', $request->query('status_id'));
        }
        if ($request->has('category_id')) {
            $query->where('category_id', $request->query('category_id'));
        }
        if ($request->has('user_id')) {
            $query->where('user_id', $request->query('user_id'));
        }

        $perPage = $request->query('per_page', 50);
        $issues = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $resolvedStatus = IssueStatus::where('name', 'Resuelto')->first();

        $issues->getCollection()->transform(function ($issue) use ($resolvedStatus) {
            $resolutionTime = null;
            $assignedWorker = null;

            if ($resolvedStatus) {
                $resolvedEntry = $issue->history->firstWhere('status_id', $resolvedStatus->id);
                if ($resolvedEntry) {
                    $resolutionTime = $issue->created_at->diffInHours($resolvedEntry->changed_at);
                }
            }

            $assignment = $issue->assignments->sortByDesc('created_at')->first();
            if ($assignment && $assignment->worker) {
                $assignedWorker = [
                    'id' => $assignment->worker->id,
                    'first_name' => $assignment->worker->first_name,
                    'last_name' => $assignment->worker->last_name,
                ];
            }

            return [
                'id' => $issue->id,
                'title' => $issue->title,
                'category' => $issue->category->name,
                'status' => $issue->status->name,
                'created_by' => $issue->user ? $issue->user->first_name . ' ' . $issue->user->last_name : null,
                'created_at' => $issue->created_at->toDateTimeString(),
                'resolution_time_hours' => $resolutionTime !== null ? round($resolutionTime, 2) : null,
                'assigned_worker' => $assignedWorker,
                'upvotes_count' => $issue->upvotes_count,
                'comments_count' => $issue->comments_count,
                'location' => $issue->location,
            ];
        });

        return response()->json($issues);
    }

    // =============================
    // PDF METHODS
    // =============================

    public function pdfSummary(Request $request)
    {
        [$from, $to] = $this->getDateRange($request);
        $data = $this->summary($request)->getData(true);
        $data['from'] = $from;
        $data['to'] = $to;
        $pdf = Pdf::loadView('reports.pdf.summary', $data);
        return $pdf->download("resumen-{$from}-{$to}.pdf");
    }

    public function pdfByCategory(Request $request)
    {
        [$from, $to] = $this->getDateRange($request);
        $data = $this->byCategory($request)->getData(true);
        $pdf = Pdf::loadView('reports.pdf.by-category', $data);
        return $pdf->download("reporte-por-categoria-{$from}-{$to}.pdf");
    }

    public function pdfByWorker(Request $request)
    {
        [$from, $to] = $this->getDateRange($request);
        $data = $this->byWorker($request)->getData(true);
        $pdf = Pdf::loadView('reports.pdf.by-worker', $data);
        return $pdf->download("reporte-por-trabajador-{$from}-{$to}.pdf");
    }

    public function pdfByDate(Request $request)
    {
        [$from, $to] = $this->getDateRange($request);
        $data = $this->byDate($request)->getData(true);
        $pdf = Pdf::loadView('reports.pdf.by-date', $data);
        return $pdf->download("reporte-por-fecha-{$from}-{$to}.pdf");
    }

    public function pdfResolutionTimes(Request $request)
    {
        [$from, $to] = $this->getDateRange($request);
        $data = $this->resolutionTimes($request)->getData(true);
        if (isset($data['message'])) {
            return response()->json($data, 404);
        }
        $pdf = Pdf::loadView('reports.pdf.resolution-times', $data);
        return $pdf->download("tiempos-resolucion-{$from}-{$to}.pdf");
    }

    public function pdfDetails(Request $request)
    {
        [$from, $to] = $this->getDateRange($request);
        $issues = $this->details($request)->getData(true);

        $items = [];
        if (isset($issues['data'])) {
            $items = $issues['data'];
        } elseif (isset($issues[0])) {
            $items = $issues;
        }

        $pdf = Pdf::loadView('reports.pdf.details', [
            'from' => $from,
            'to' => $to,
            'issues' => $items,
        ]);
        return $pdf->download("detalle-incidencias-{$from}-{$to}.pdf");
    }
}
