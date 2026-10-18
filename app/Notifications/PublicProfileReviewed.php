<?php

namespace App\Notifications;

use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PublicProfileReviewed extends Notification
{
    use Queueable;

    public function __construct(public Student $student)
    {
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return $this->student->isPubliclyListed()
            ? ['title' => 'Your public profile is live', 'body' => 'Visitors and companies can now see your profile on Nextward.', 'action' => parse_url($this->student->publicUrl(), PHP_URL_PATH)]
            : ['title' => 'Your public profile was not approved', 'body' => 'Your profile stays private. You can update it and ask again from Settings.', 'action' => '/settings'];
    }
}
