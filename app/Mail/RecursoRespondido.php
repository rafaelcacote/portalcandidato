<?php

namespace App\Mail;

use App\Models\Modules\Candidate\Models\ApplicationAppeal;
use App\Modules\Shared\Enums\ApplicationAppealStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RecursoRespondido extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly ApplicationAppeal $appeal) {}

    public function envelope(): Envelope
    {
        $processTitle = $this->appeal->application?->selectionProcess?->titulo ?? 'Processo seletivo';

        return new Envelope(
            subject: "Resposta ao seu recurso — {$processTitle}",
        );
    }

    public function content(): Content
    {
        $status = ApplicationAppealStatus::tryFrom($this->appeal->status);

        return new Content(
            markdown: 'emails.recurso-respondido',
            with: [
                'appeal' => $this->appeal,
                'statusLabel' => $status?->label() ?? $this->appeal->status,
                'applicationUrl' => route('candidate.applications.show', $this->appeal->application_id),
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
