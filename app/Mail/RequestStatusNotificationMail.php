<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RequestStatusNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $title;
    public string $message;
    public ?string $link;

    public function __construct(string $title, string $message, ?string $link = null)
    {
        $this->title = $title;
        $this->message = $message;
        $this->link = $link;
    }

    public function build()
    {
        return $this->subject($this->title)
                    ->view('emails.request-status-notification')
                    ->with([
                        'title' => $this->title,
                        'message' => $this->message,
                        'link' => $this->link,
                    ]);
    }
}
