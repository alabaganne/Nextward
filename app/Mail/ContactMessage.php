<?php

namespace App\Mail;

use App\Support\MailText;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ContactMessage extends Mailable
{
    public const TOPICS = [
        'help' => 'Help request',
        'bug' => 'Bug report',
        'feature' => 'Feature idea',
        'listing' => 'Problem with a listing',
        'business' => 'Hiring or partnership',
        'other' => 'Other',
    ];

    public function __construct(public array $data, public ?array $screenshot = null)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Contact form: '.self::TOPICS[$this->data['topic']].' from '.$this->data['name'],
            replyTo: [new Address($this->data['email'], $this->data['name'])],
        );
    }

    public function content(): Content
    {
        $plain = fn (string $key, int $limit) => filled($this->data[$key] ?? null) ? MailText::plain($this->data[$key], $limit) : null;

        return new Content(markdown: 'mail.contact-message', with: [
            'name' => $plain('name', 120),
            'email' => $plain('email', 200),
            'topic' => self::TOPICS[$this->data['topic']],
            'body' => $plain('message', 5000),
            'pageUrl' => $plain('page_url', 300),
            'account' => $plain('account', 300),
            'browser' => $plain('browser', 300),
            'hasScreenshot' => $this->screenshot !== null,
            'sentAt' => now()->timezone('Africa/Tunis')->format('j M Y \a\t H:i'),
        ]);
    }

    public function attachments(): array
    {
        if (! $this->screenshot) {
            return [];
        }

        return [Attachment::fromData(fn () => $this->screenshot['data'], $this->screenshot['name'])->withMime($this->screenshot['mime'])];
    }
}
