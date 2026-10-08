<?php

namespace App\Mail;

use App\Models\CashRegister;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegisterCloseReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public CashRegister $register,
        public array $settings,
        public $topCategories,
        public ?string $pdfBinary = null,
    ) {
    }

    public function envelope(): Envelope
    {
        $isDay = $this->register->mode === 'day_end';
        $label = $isDay ? 'Day End Report' : 'Shift Report';
        $who = $this->register->user?->name ?? 'Cashier';
        $when = \App\Models\Setting::formatDateTime($this->register->closed_at ?? now(), 'Y-m-d H:i');

        return new Envelope(
            subject: sprintf('%s — %s — %s', $label, $who, $when),
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: view('emails.register-close-report', [
                'register' => $this->register,
                'settings' => $this->settings,
                'topCategories' => $this->topCategories,
                'cashierBreakdown' => $this->register->cashierBreakdown(),
            ])->render(),
        );
    }

    public function attachments(): array
    {
        if (! $this->pdfBinary) {
            return [];
        }

        $isDay = $this->register->mode === 'day_end';
        $name = ($isDay ? 'day-end' : 'shift').'-report-'.$this->register->id.'.pdf';

        return [
            Attachment::fromData(fn () => $this->pdfBinary, $name)
                ->withMime('application/pdf'),
        ];
    }
}
