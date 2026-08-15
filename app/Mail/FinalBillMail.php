<?php

namespace App\Mail;

use App\Models\GuestStay;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FinalBillMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public GuestStay $guestStay,
        private readonly string $billPdf,
        public readonly ?string $additionalDescription,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Final bill - '.$this->guestStay->company->name);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.final-bill');
    }

    public function attachments(): array
    {
        $attachments = [Attachment::fromData(fn () => $this->billPdf, 'final-bill.pdf')->withMime('application/pdf')];
        if (filled($this->additionalDescription)) {
            $description = $this->additionalDescription;
            $attachments[] = Attachment::fromData(fn () => $description, 'additional-services.txt')->withMime('text/plain');
        }

        return $attachments;
    }
}
