<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactInquiryMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $fullName,
        public string $phone,
        public string $email,
        public string $topic,
        public string $messageBody,
        public string $locale = 'uz',
    ) {}

    public function envelope(): Envelope
    {
        $replyTo = [];
        if ($this->email !== '') {
            $replyTo[] = new Address($this->email, $this->fullName);
        }

        return new Envelope(
            subject: 'Kitobchi — saytdan yangi murojaat: '.$this->fullName,
            replyTo: $replyTo,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.contact.inquiry',
        );
    }
}
