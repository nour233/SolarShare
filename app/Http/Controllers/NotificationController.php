<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * Display a listing of notifications for the authenticated user.
     */
    public function index(Request $request): View
    {
        $filter = $request->query('filter', 'all');

        $query = $request->user()->notifications();

        if ($filter === 'unread') {
            $query = $request->user()->unreadNotifications();
        }

        $notifications = $query->paginate(15)->withQueryString();
        $unreadCount   = $request->user()->unreadNotifications()->count();

        // Return client or admin view based on user role/layout context
        $view = $request->user()->isAdmin() && $request->routeIs('admin.*')
            ? 'back.notifications.index'
            : 'notifications.index';

        return view($view, compact('notifications', 'filter', 'unreadCount'));
    }

    /**
     * Return JSON for unread count and latest notifications (for navbar dropdown / AJAX polling).
     */
    public function feed(Request $request): JsonResponse
    {
        $user = $request->user();

        $unreadCount = $user->unreadNotifications()->count();
        $recent = $user->notifications()->take(6)->get()->map(function ($notification) {
            return [
                'id'         => $notification->id,
                'read'       => $notification->read(),
                'created_at' => $notification->created_at->diffForHumans(),
                'data'       => $notification->data,
            ];
        });

        return response()->json([
            'unread_count'  => $unreadCount,
            'notifications' => $recent,
        ]);
    }

    /**
     * Mark a specific notification as read and redirect to its action URL.
     */
    public function read(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        $targetUrl = $notification->data['url'] ?? url()->previous();

        return redirect()->to($targetUrl);
    }

    /**
     * Mark a specific notification as read via AJAX.
     */
    public function markAsReadAjax(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json([
            'success'      => true,
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('status', 'Toutes les notifications ont été marquées comme lues.');
    }

    /**
     * Delete a notification.
     */
    public function destroy(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->delete();

        return back()->with('status', 'Notification supprimée.');
    }
}

