<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class NotificationController extends BaseController
{
    public function index(Request $request)
    {
        $notifications = $request->user()
            ->notifications()
            ->paginate(20)
            ->withQueryString();

        return view('notifications.index', compact('notifications'));
    }

    public function markAsRead(Request $request, int $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->update(['is_read' => true]);

        return back()->with('success', 'Notification marked as read.');
    }

    public function markAsUnread(Request $request, int $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->update(['is_read' => false]);

        return back()->with('success', 'Notification marked as unread.');
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()
            ->notifications()
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return back()->with('success', 'All notifications marked as read.');
    }

    public function cleanup(Request $request): RedirectResponse
    {
        // Delete old read notifications (older than 30 days)
        $request->user()
            ->notifications()
            ->where('is_read', true)
            ->where('created_at', '<', now()->subDays(30))
            ->delete();

        return back()->with('success', 'Old notifications cleaned up successfully.');
    }

    public function open(Request $request, int $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);

        if (! $notification->is_read) {
            $notification->update(['is_read' => true]);
        }

        $targetUrl = $notification->url ?: route('dashboard');

        if (! $this->isSafeRedirectTarget($targetUrl)) {
            return redirect()->route('dashboard')
                ->with('error', 'Invalid notification link.');
        }

        return redirect($targetUrl);
    }

    private function isSafeRedirectTarget(string $url): bool
    {
        // Reject protocol-relative URLs (e.g. //evil.example) which browsers treat as external.
        if (Str::startsWith($url, ['//'])) {
            return false;
        }

        if (Str::startsWith($url, ['/'])) {
            return true;
        }

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);
        $targetHost = parse_url($url, PHP_URL_HOST);
        $appHost = parse_url(config('app.url'), PHP_URL_HOST);

        if (! in_array(strtolower((string) $scheme), ['http', 'https'], true)) {
            return false;
        }

        return is_string($targetHost)
            && is_string($appHost)
            && strcasecmp($targetHost, $appHost) === 0;
    }
}
