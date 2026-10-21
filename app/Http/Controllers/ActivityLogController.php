<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $logs = ActivityLog::query()
            ->when($request->string('search')->isNotEmpty(), fn ($q) => $q->where('description', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->input('action')))
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->integer('user_id')))
            ->when($request->date('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('date_from')))
            ->when($request->date('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('date_to')))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('activity-logs.index', [
            'logs' => $logs,
            'filters' => $request->only(['search', 'action', 'user_id', 'date_from', 'date_to']),
            'actions' => ActivityLog::query()
                ->distinct()
                ->orderBy('action')
                ->pluck('action')
                ->map(fn ($action) => $action)
                ->all(),
            'users' => User::orderBy('name')->pluck('name', 'id'),
        ]);
    }
}
