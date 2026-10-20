<?php

namespace App\Notifications;

use App\Models\Candidate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PublicProfileReviewed extends Notification
{
    use Queueable;

    public function __construct(public Candidate $candidate)
    {
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return $this->candidate->isPubliclyListed()
            ? ['title' => 'Your public profile is live', 'body' => 'Visitors and companies can now see your profile on Nextward.', 'action' => parse_url($this->candidate->publicUrl(), PHP_URL_PATH)]
            : ['title' => 'Your public profile was not approved', 'body' => 'Your profile stays private. You can update it and ask again from your profile.', 'action' => route('profile.show', [], false)];
    }
}
