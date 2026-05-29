@extends('pdfs.layout')

@section('title', $declarationTitle)
@section('document-title', $declarationTitle)

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
            <td class="label">Processo seletivo</td>
            <td>{{ $process->titulo }}</td>
        </tr>
        <tr>
            <td class="label">Etapa</td>
            <td>{{ $stage->nome }}</td>
        </tr>
        <tr>
            <td class="label">Situação da inscrição</td>
            <td>{{ strtoupper(str_replace('_', ' ', $application->status)) }}</td>
        </tr>
    </table>

    @if ($declarationKind === 'aprovacao')
        <p class="body-text">
            Declaramos, para os devidos fins profissionais e institucionais, que o(a) candidato(a)
            <strong>{{ $candidate->name }}</strong>, inscrito(a) no processo seletivo
            <strong>{{ $process->titulo }}</strong> (protocolo <strong>{{ $application->numero_protocolo }}</strong>),
            foi <strong>APROVADO(A)</strong> na etapa <strong>{{ $stage->nome }}</strong>.
        </p>
    @else
        <p class="body-text">
            Declaramos, para os devidos fins profissionais e institucionais, que o(a) candidato(a)
            <strong>{{ $candidate->name }}</strong>, inscrito(a) no processo seletivo
            <strong>{{ $process->titulo }}</strong> (protocolo <strong>{{ $application->numero_protocolo }}</strong>),
            participou / está participando da etapa <strong>{{ $stage->nome }}</strong> deste processo seletivo.
        </p>
    @endif

    <p class="body-text">
        A presente declaração é emitida com base nos registros do sistema de inscrições e poderá ser
        utilizada para comprovação perante empregadores, conselhos profissionais e demais órgãos,
        nos termos do edital do processo.
    </p>

    <div class="signature">
        <div class="line"></div>
        <div>{{ $institution }}</div>
        <div style="font-size: 9pt; margin-top: 4px;">Documento emitido eletronicamente</div>
    </div>
@endsection
