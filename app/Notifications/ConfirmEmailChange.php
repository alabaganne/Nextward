<?php

namespace App\Notifications;

use App\Support\MailText;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

// Sent to the current address so its owner confirms the move to a new one.
class ConfirmEmailChange extends Notification
{
    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $minutes = (int) config('auth.verification.expire', 60);
        $url = URL::temporarySignedRoute('email.change.confirm', now()->addMinutes($minutes), [
            'user' => $notifiable->id,
            'hash' => sha1($notifiable->pending_email),
        ]);

        return (new MailMessage)
            ->subject('Confirm your new email for Nextward')
            ->markdown('mail.email-change', [
                'name' => MailText::plain(MailText::firstName($notifiable->name), 80),
                'old' => MailText::plain($notifiable->email, 255),
                'new' => MailText::plain($notifiable->pending_email, 255),
                'url' => $url,
                'minutes' => $minutes,
            ]);
    }
}
