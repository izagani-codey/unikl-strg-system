<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AuditLogController extends BaseController
{
    public function index(Request $request)
    {
        $logs = AuditLog::query()
            ->with(['request', 'actor'])
            ->latest('created_at');

        if ($request->filled('status')) {
            $status = $request->integer('status');
            $logs->where(function (Builder $q) use ($status) {
                $q->where('from_status', $status)
                    ->orWhere('to_status', $status);
            });
        }

        if ($request->filled('actor')) {
            $actor = trim($request->input('actor'));
            $logs->whereHas('actor', function (Builder $q) use ($actor) {
                $q->where('name', 'like', "%{$actor}%")
                    ->orWhere('email', 'like', "%{$actor}%");
            });
        }

        if ($request->filled('reference')) {
            $reference = trim($request->input('reference'));
            $logs->whereHas('request', function (Builder $q) use ($reference) {
                $q->where('ref_number', 'like', "%{$reference}%");
            });
        }

        if ($request->filled('date_from')) {
            $logs->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $logs->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $logs = $logs->paginate(25)->withQueryString();

        return view('audit-logs.index', compact('logs'));
    }
}
