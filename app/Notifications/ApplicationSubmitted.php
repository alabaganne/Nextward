<?php

namespace App\Notifications;

use App\Http\Resources\NotificationResource;
use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

use App\Models\Notification as NotificationModel;

class ApplicationSubmitted extends Notification
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
        return [
			// 'mail',
			'database',
			'broadcast'
		];
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
                    ->line('A new application has been submitted to ' . $this->application->internship->title . '.')
                    ->action('View details', route('applications.show', $this->application));
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        $studentName = e($this->application->student->user->name);
        $internshipTitle = e($this->application->internship->title);

        return [
            'title' => "{$this->application->student->user->name}'s Application",
            'body' => "<span>{$studentName}</span> has applied to <span>{$internshipTitle}</span>.",
            'action' => "/applications/{$this->application->id}"
        ];
    }

	public function toBroadcast($notifiable)
	{
		return new BroadcastMessage([
			'toast' => [
				'message' => 'You received a new internship application.',
				'type' => 'notification'
			],
			'data' => new NotificationResource(NotificationModel::find($this->id))
		]);
	}
}
