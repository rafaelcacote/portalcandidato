<x-mail::message>
# Documento recusado na avaliação

Um dos documentos da sua inscrição foi recusado durante a análise do processo seletivo.

**Documento:** {{ $document->nome_arquivo }}  
**Motivo:** {{ $document->motivo_recusa ?? 'Não informado' }}

Após a finalização da inscrição, não é possível enviar um novo arquivo pelo portal. Em caso de dúvida, entre em contato com a organização do processo.

Obrigado,<br>
{{ config('app.name') }}
</x-mail::message>
