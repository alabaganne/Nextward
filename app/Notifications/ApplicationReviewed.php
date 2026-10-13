<?php

namespace App\Notifications;

use App\Http\Resources\NotificationResource;
use App\Models\Application;
use App\Models\Notification as NotificationModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationReviewed extends Notification
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
        return ['database', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our application!');
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
        $internshipTitle = e($this->application->internship->title);

        return [
            'title' => "{$this->application->company->display_name}'s Reply",
            'body' => "<span>{$companyName}</span> has reviewed your application for <span>{$internshipTitle}</span>.",
            'action' => "/applications/{$this->application->id}",
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
