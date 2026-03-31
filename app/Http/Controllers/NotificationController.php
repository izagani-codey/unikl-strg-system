<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()
            ->notifications()
            ->paginate(20)
            ->withQueryString();

        return view('notifications.index', compact('notifications'));
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()
            ->notifications()
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return back()->with('success', 'All notifications marked as read.');
    }

    public function open(Request $request, int $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);

        if (! $notification->is_read) {
            $notification->update(['is_read' => true]);
        }

        return redirect($notification->url ?: route('dashboard'));
    }
}
