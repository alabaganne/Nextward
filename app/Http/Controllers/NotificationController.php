<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class NotificationController extends Controller
{
    /**
     * Mark a notification as read, then follow its action link.
     */
    public function store(Request $request, Notification $notification)
    {
        abort_unless((int) $notification->notifiable_id === $request->user()->id, 403);

        if (! $notification->read_at) {
            $notification->update([
                'read_at' => now(),
            ]);
        }

        return Redirect::to($notification->data['action'] ?? url()->previous());
    }

    public function clear()
    {
        auth()->user()->notifications()->delete();

        return Redirect::back();
    }
}
