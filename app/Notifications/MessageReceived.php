<?php

namespace App\Notifications;

use App\Models\Message;
use App\Support\MailText;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Cache;

class MessageReceived extends Notification implements ShouldQueue
{
    use Queueable;

    // One email per conversation in this many minutes, so a long chat doesn't flood the inbox.
    public const QUIET_MINUTES = 30;

    public function __construct(public Message $message)
    {
    }

    public static function shouldEmail(Message $message): bool
    {
        return Cache::add(
            'message-email:'.$message->from_id.':'.$message->to_id,
            true,
            now()->addMinutes(self::QUIET_MINUTES),
        );
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $sender = $this->message->sender;
        $senderName = $sender->isCompany() ? ($sender->userable?->display_name ?? $sender->name) : $sender->name;

        return (new MailMessage)
            ->subject($senderName.' sent you a message on Nextward')
            ->markdown('mail.message-received', [
                'sender' => MailText::plain($senderName, 120),
                'recipient' => MailText::plain(MailText::firstName($notifiable->name), 80),
                'excerpt' => MailText::plain($this->message->text, 500),
                'url' => route('messages.show', $sender),
            ]);
    }
}
