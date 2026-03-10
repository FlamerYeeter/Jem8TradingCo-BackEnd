<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactReply extends Mailable
{
    use Queueable, SerializesModels;

    public string $replyMessage;
    public string $customerName;

    public function __construct(string $customerName, string $replyMessage)
    {
        $this->customerName   = $customerName;
        $this->replyMessage   = $replyMessage;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reply from Jem8 Trading Co',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact_reply',
        );
    }
}
