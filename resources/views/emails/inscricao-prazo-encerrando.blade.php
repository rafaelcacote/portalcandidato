<x-mail::layout>
<x-slot:header>
<x-mail::header :url="config('app.url')">
<img
    src="{{ $logoUrl }}"
    alt="{{ config('app.name') }}"
    width="220"
    height="75"
    style="display: block; height: 75px; width: auto; max-width: 220px; border: 0; outline: none; text-decoration: none;"
>
</x-mail::header>
</x-slot:header>

# Faltam 2 dias para encerrar as inscrições

Olá, **{{ $candidateName }}**!

O processo seletivo **{{ $processTitle }}** está chegando ao fim do período de inscrições.
@if ($deadlineFormatted)
O prazo para envio da inscrição encerra em **{{ $deadlineFormatted }}**.
@endif

Identificamos que você iniciou uma inscrição neste processo, mas ainda **não a finalizou**. Para participar do processo seletivo, conclua todas as etapas e envie sua inscrição na etapa **Revisar Inscrição**.

<x-mail::button :url="$continueUrl" color="primary">
Continuar e finalizar inscrição
</x-mail::button>

Se você não deseja mais se inscrever neste processo, pode ignorar este e-mail.

Atenciosamente,<br>
{{ config('app.name') }}

<x-slot:footer>
<x-mail::footer>
© {{ date('Y') }} {{ config('app.name') }}. {{ __('All rights reserved.') }}
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
