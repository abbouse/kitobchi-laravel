<?php

namespace App\Mail;

use App\Models\CareerApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CareerApplicantReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public CareerApplication $application,
        public string $bodyText,
    ) {}

    public function envelope(): Envelope
    {
        $addr = config('mail.reply_to.address') ?: config('mail.from.address');
        $name = config('mail.reply_to.name') ?: 'Kitobchi';

        return new Envelope(
            subject: 'Kitobchi — murojaatingiz bo‘yicha javob',
            replyTo: $addr ? [new Address($addr, $name)] : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.career.applicant-reply',
        );
    }
}
