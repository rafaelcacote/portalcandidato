<x-mail::message>
# Documento recusado

Um dos documentos da sua inscrição foi recusado.

**Documento:** {{ $document->nome_arquivo }}  
**Motivo:** {{ $document->motivo_recusa ?? 'Não informado' }}

Obrigado,<br>
{{ config('app.name') }}
</x-mail::message>
