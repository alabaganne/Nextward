<?php

namespace App\Notifications;

use App\Http\Resources\NotificationResource;
use App\Models\Application;
use App\Support\MailText;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

use App\Models\Notification as NotificationModel;

class ApplicationSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    private $application;

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
        $role = $application->opportunity->title;

        return (new MailMessage)
            ->subject('New application for '.$role)
            ->markdown('mail.application-submitted', [
                'company' => MailText::plain($application->company->display_name, 120),
                'candidate' => MailText::plain($application->candidate->user->name, 120),
                'role' => MailText::plain($role, 160),
                'excerpt' => filled($application->message ?: $application->cover_letter)
                    ? MailText::plain($application->message ?: $application->cover_letter, 400) : null,
                'attachments' => count($application->attachments ?? []),
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
        $candidateName = e($this->application->candidate->user->name);
        $opportunityTitle = e($this->application->opportunity->title);

        return [
            'title' => "{$this->application->candidate->user->name}'s Application",
            'body' => "<span>{$candidateName}</span> has applied to <span>{$opportunityTitle}</span>.",
            'action' => route('applications.show', $this->application->id, false)
        ];
    }

	public function toBroadcast($notifiable)
	{
		return new BroadcastMessage([
			'toast' => [
				'message' => 'You received a new application.',
				'type' => 'notification'
			],
			'data' => new NotificationResource(NotificationModel::find($this->id))
		]);
	}
}
