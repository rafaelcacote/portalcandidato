@extends('pdfs.layout')

@section('title', 'Lista de candidatos')
@section('document-title', 'Lista de candidatos')

@push('styles')
    body {
        padding: 20px 18px;
        font-size: 9pt;
        line-height: 1.35;
    }
    .header {
        padding-bottom: 10px;
        margin-bottom: 14px;
    }
    .header h1 {
        font-size: 13pt;
    }
    .candidates-list {
        width: 100%;
        border-collapse: collapse;
        font-size: 8pt;
        table-layout: fixed;
    }
    .candidates-list th,
    .candidates-list td {
        border: 1px solid #cbd5e1;
        padding: 4px 6px;
        vertical-align: middle;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .candidates-list th {
        background: #f8fafc;
        text-align: left;
        font-weight: bold;
    }
    .candidates-list .col-protocol {
        width: 18%;
    }
    .candidates-list .col-name {
        width: 42%;
    }
    .candidates-list .col-line {
        width: 24%;
    }
    .candidates-list .col-cpf {
        width: 16%;
    }
    .candidates-meta {
        margin: 0 0 6px;
        font-size: 9pt;
    }
    .candidates-meta-last {
        margin: 0 0 12px;
        font-size: 9pt;
    }
    .footer {
        margin-top: 24px;
        padding-top: 10px;
        font-size: 8pt;
    }
@endpush

@section('content')
    <p class="candidates-meta"><strong>Processo:</strong> {{ $process->titulo }}</p>
    <p class="candidates-meta-last"><strong>Data:</strong> {{ $generatedAt }}</p>

    <table class="candidates-list">
        <thead>
            <tr>
                <th class="col-protocol">Cod. inscrição</th>
                <th class="col-name">Nome completo</th>
                <th class="col-line">Linha</th>
                <th class="col-cpf">CPF</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($candidates as $candidate)
                <tr>
                    <td class="col-protocol">{{ $candidate['numero_protocolo'] }}</td>
                    <td class="col-name">{{ $candidate['nome_completo'] }}</td>
                    <td class="col-line">{{ $candidate['linha_pesquisa_label'] ?? '—' }}</td>
                    <td class="col-cpf">{{ $candidate['cpf_mascarado'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align: center; color: #64748b; white-space: normal;">
                        Nenhum candidato inscrito neste processo.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
