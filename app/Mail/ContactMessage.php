<?php

namespace App\Mail;

use App\Support\MailText;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ContactMessage extends Mailable
{
    public const TOPICS = [
        'general' => 'General question',
        'listing' => 'Problem with a listing',
        'employer' => 'Hiring on Nextward',
        'partnership' => 'Source or partnership',
        'feedback' => 'Product feedback',
    ];

    public function __construct(public array $data)
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
        return new Content(markdown: 'mail.contact-message', with: [
            'name' => MailText::plain($this->data['name'], 120),
            'email' => MailText::plain($this->data['email'], 200),
            'topic' => self::TOPICS[$this->data['topic']],
            'body' => MailText::plain($this->data['message'], 5000),
            'pageUrl' => filled($this->data['page_url'] ?? null) ? MailText::plain($this->data['page_url'], 300) : null,
            'sentAt' => now()->timezone('Africa/Tunis')->format('j M Y \a\t H:i'),
        ]);
    }
}
