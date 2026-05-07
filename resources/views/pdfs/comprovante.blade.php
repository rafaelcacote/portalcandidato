<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Comprovante de Inscrição</title>
</head>
<body>
    <h1>Comprovante de Inscrição</h1>
    <p>Protocolo: {{ $application->numero_protocolo }}</p>
    <p>Candidato: {{ $application->user->name }}</p>
    <p>Processo: {{ $application->selectionProcess->titulo }}</p>
    <p>Data: {{ optional($application->finalizada_em)->format('d/m/Y H:i') }}</p>
</body>
</html>
