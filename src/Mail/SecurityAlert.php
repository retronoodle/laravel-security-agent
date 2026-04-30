<?php

namespace Timmonaghan\SecurityAgent\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class SecurityAlert extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $ip,
        public readonly string $patternType,
        public readonly float $confidence,
        public readonly string $summary,
        public readonly Carbon $timestamp,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[Security Alert] {$this->patternType} detected from {$this->ip}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'security-agent::emails.security-alert',
        );
    }
}
