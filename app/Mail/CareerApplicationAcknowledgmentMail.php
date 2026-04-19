<?php

namespace App\Mail;

use App\Models\CareerApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CareerApplicationAcknowledgmentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public CareerApplication $application) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Kitobchi — arizangiz qabul qilindi',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.career.acknowledgment',
        );
    }
}
