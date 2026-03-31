<?php

namespace App\View\Components;

use App\Models\Request;
use Illuminate\View\Component;
use Illuminate\View\View;

class ActivityTimeline extends Component
{
    public function __construct(
        public Request $request
    ) {}

    public function render(): View
    {
        // Get all audit logs and comments, ordered by date
        $activities = collect();

        // Add audit logs (status changes)
        $this->request->auditLogs->each(function ($log) use ($activities) {
            $activities->push([
                'type' => 'status_change',
                'date' => $log->created_at,
                'actor' => $log->actor,
                'from_status' => $log->from_status ? \App\Enums\RequestStatus::from($log->from_status) : null,
                'to_status' => \App\Enums\RequestStatus::from($log->to_status),
                'note' => $log->note,
            ]);
        });

        // Add comments
        $this->request->comments->each(function ($comment) use ($activities) {
            $activities->push([
                'type' => 'comment',
                'date' => $comment->created_at,
                'actor' => $comment->user,
                'content' => $comment->content,
                'is_internal' => $comment->is_internal,
            ]);
        });

        // Sort by date (newest first)
        $activities = $activities->sortByDesc('date');

        return view('components.activity-timeline', [
            'activities' => $activities,
        ]);
    }
}
