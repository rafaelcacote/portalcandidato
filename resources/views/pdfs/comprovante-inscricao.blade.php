@extends('pdfs.layout')

@section('title', 'Comprovante de Inscrição')
@section('document-title', 'Comprovante de Inscrição')

@section('content')
    <table class="meta">
        <tr>
            <td class="label">Protocolo</td>
            <td>{{ $application->numero_protocolo }}</td>
        </tr>
        <tr>
            <td class="label">Candidato(a)</td>
            <td>{{ $candidate->name }}</td>
        </tr>
        <tr>
            <td class="label">CPF</td>
            <td>
                @php
                    $cpf = preg_replace('/\D/', '', (string) $candidate->cpf);
                    $cpfFormatted = strlen($cpf) === 11
                        ? preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf)
                        : $candidate->cpf;
                @endphp
                {{ $cpfFormatted }}
            </td>
        </tr>
        <tr>
            <td class="label">E-mail</td>
            <td>{{ $candidate->email }}</td>
        </tr>
        <tr>
            <td class="label">Processo seletivo</td>
            <td>{{ $process->titulo }}</td>
        </tr>
        <tr>
            <td class="label">Data da inscrição</td>
            <td>{{ optional($application->finalizada_em)->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td class="label">Situação</td>
            <td>{{ strtoupper(str_replace('_', ' ', $application->status)) }}</td>
        </tr>
    </table>

    <p class="body-text">
        Certificamos, para os devidos fins, que o(a) candidato(a) acima identificado(a) realizou
        inscrição no processo seletivo <strong>{{ $process->titulo }}</strong>, sob o protocolo
        <strong>{{ $application->numero_protocolo }}</strong>, na data indicada neste documento.
    </p>

    <p class="body-text">
        Este comprovante é válido para apresentação a órgãos e instituições que exijam comprovação
        de participação em processo seletivo, conforme regulamento do edital.
    </p>

    <div class="signature">
        <div class="line"></div>
        <div>{{ $institution }}</div>
        <div style="font-size: 9pt; margin-top: 4px;">Documento emitido eletronicamente</div>
    </div>
@endsection
