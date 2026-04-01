<?php

namespace App\Notifications;

use App\Models\Request;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OverrideNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Request $request,
        public string $message,
        public string $actionType
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Staff 2 Override Action',
            'message' => $this->message,
            'action_type' => $this->actionType,
            'request_id' => $this->request->id,
            'ref_number' => $this->request->ref_number,
            'priority' => 'high',
            'icon' => 'shield-exclamation',
            'color' => 'purple',
        ];
    }
}
