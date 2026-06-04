<?php

namespace App\Mail;

use App\Models\Modules\Candidate\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InscricaoPrazoEncerrando extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Application $application) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Faltam 2 dias para encerrar as inscrições — finalize sua inscrição',
        );
    }

    public function content(): Content
    {
        $this->application->loadMissing(['user', 'selectionProcess']);

        $deadline = $this->application->selectionProcess?->inscricao_fim_em;

        return new Content(
            markdown: 'emails.inscricao-prazo-encerrando',
            with: [
                'application' => $this->application,
                'candidateName' => $this->application->user?->name ?? 'Candidato(a)',
                'processTitle' => $this->application->selectionProcess?->titulo ?? 'Processo seletivo',
                'deadlineFormatted' => $deadline !== null
                    ? $deadline->timezone(config('app.timezone'))->format('d/m/Y H:i')
                    : null,
                'continueUrl' => route('candidate.applications.show', $this->application),
                'logoUrl' => url('img/logo_proensp_email.png'),
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
