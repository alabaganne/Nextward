<?php

namespace App\Notifications;

use App\Http\Resources\NotificationResource;
use App\Models\Application;
use App\Support\MailText;
use App\Models\Notification as NotificationModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationReviewed extends Notification implements ShouldQueue
{
    use Queueable;

    public $application;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', 'database', 'broadcast'];
    }

    // Only the email waits in the queue; the in-app notice shows right away.
    public function viaConnections(): array
    {
        return ['database' => 'sync', 'broadcast' => 'sync'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $application = $this->application;
        $company = $application->company->display_name;
        $role = $application->opportunity->title;

        [$subject, $headline, $body, $tip] = match ($application->status) {
            'interview' => ["{$company} would like to interview you", 'You have an interview request', "{$company} wants to talk with you about {$role}.", 'Watch your inbox and your Nextward messages for the next steps.'],
            'offer' => ["Good news from {$company}", 'You received an offer', "{$company} made you an offer for {$role}. Congratulations!", 'Reply to the employer soon so they know your answer.'],
            'closed' => ["Update on your application to {$company}", 'Your application was closed', "{$company} decided not to move forward with your application for {$role}.", 'It happens to everyone. New roles come in every day, so keep going.'],
            'review' => ["{$company} is reviewing your application", 'Your application is under review', "{$company} is now reviewing your application for {$role}.", null],
            default => ["{$company} saw your application", 'Your application was seen', "{$company} opened your application for {$role}.", null],
        };

        return (new MailMessage)
            ->subject($subject)
            ->markdown('mail.application-updated', [
                'candidate' => MailText::plain(MailText::firstName($notifiable->name), 80),
                'headline' => MailText::plain($headline, 200),
                'body' => MailText::plain($body, 400),
                'tip' => $tip,
                'role' => MailText::plain($role, 160),
                'company' => MailText::plain($company, 120),
                'statusLabel' => ucfirst($application->status),
                'url' => route('applications.show', $application),
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        $companyName = e($this->application->company->display_name);
        $opportunityTitle = e($this->application->opportunity->title);

        return [
            'title' => "{$this->application->company->display_name}'s Reply",
            'body' => "<span>{$companyName}</span> has reviewed your application for <span>{$opportunityTitle}</span>.",
            'action' => route('applications.show', $this->application->id, false),
        ];
    }

	public function toBroadcast($notifiable)
	{
		return new BroadcastMessage([
			'toast' => [
				'message' => "{$this->application->company->display_name} has reviewed your application.",
				'type' => 'notification',
			],
			'data' => new NotificationResource(NotificationModel::find($this->id))
		]);
	}
}
