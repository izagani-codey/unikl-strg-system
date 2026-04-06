<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
