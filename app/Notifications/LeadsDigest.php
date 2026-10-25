<?php

namespace App\Notifications;

use App\Support\MailText;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

// The day's job leads, sent to the owner after the daily run when LEADS_DIGEST_EMAIL is on.
class LeadsDigest extends Notification
{
    use Queueable;

    public function __construct(public Collection $leads) {}

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $count = $this->leads->count();
        return (new MailMessage)
            ->subject($count === 1 ? 'One new job lead today' : "{$count} new job leads today")
            ->markdown('mail.leads-digest', [
                'leads' => $this->leads->map(fn ($lead) => [
                    'company' => MailText::plain($lead->company_name, 120),
                    'title' => MailText::plain($lead->title, 160),
                    'reason' => MailText::plain(implode(' · ', array_slice($lead->reasons ?? [], 0, 3)), 200),
                ])->all(),
                'url' => route('leads.index'),
            ]);
    }
}
