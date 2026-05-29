<x-mail::message>
# Resposta ao seu recurso

Olá{{ $appeal->application?->user?->name ? ', ' . $appeal->application->user->name : '' }},

A comissão do processo seletivo analisou o recurso que você enviou
@if ($appeal->processStage)
referente à etapa **{{ $appeal->processStage->nome }}**
@endif
.

**Processo:** {{ $appeal->application?->selectionProcess?->titulo ?? '—' }}  
**Protocolo:** {{ $appeal->application?->numero_protocolo ?? '—' }}  
**Situação do recurso:** {{ $statusLabel }}

@if ($appeal->resposta)
**Resposta da comissão:**

{{ $appeal->resposta }}
@endif

<x-mail::button :url="$applicationUrl">
Ver inscrição no portal
</x-mail::button>

Você também pode consultar os detalhes na área **Recursos** da sua inscrição.

Obrigado,<br>
{{ config('app.name') }}
</x-mail::message>
