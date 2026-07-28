@extends('pdfs.layout')

@section('title', 'Lista de candidatos avaliados')
@section('document-title', 'Lista de candidatos avaliados')

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
        width: 46%;
    }
    .candidates-list .col-cpf {
        width: 18%;
    }
    .candidates-list .col-nota {
        width: 18%;
        text-align: right;
    }
    .candidates-meta {
        margin: 0 0 6px;
        font-size: 9pt;
    }
    .candidates-meta-last {
        margin: 0 0 12px;
        font-size: 9pt;
    }
    .candidates-filters {
        margin: 0 0 12px;
        padding: 8px 10px;
        border: 1px solid #cbd5e1;
        background: #f8fafc;
        font-size: 8.5pt;
    }
    .candidates-filters strong {
        display: block;
        margin-bottom: 4px;
    }
    .candidates-filters ul {
        margin: 0;
        padding-left: 16px;
    }
    .candidates-filters li {
        margin: 0 0 2px;
    }
    .footer {
        margin-top: 24px;
        padding-top: 10px;
        font-size: 8pt;
    }
@endpush

@section('content')
    <p class="candidates-meta"><strong>Processo:</strong> {{ $processTitle }}</p>
    <p class="candidates-meta"><strong>Data:</strong> {{ $generatedAt }}</p>

    @if (! empty($appliedFilters))
        <div class="candidates-filters">
            <strong>Filtros aplicados nesta listagem:</strong>
            <ul>
                @foreach ($appliedFilters as $filterLabel)
                    <li>{{ $filterLabel }}</li>
                @endforeach
            </ul>
        </div>
    @else
        <p class="candidates-meta-last"><strong>Filtros:</strong> nenhum (todos os candidatos avaliados)</p>
    @endif

    <table class="candidates-list">
        <thead>
            <tr>
                <th class="col-protocol">Código</th>
                <th class="col-name">Nome</th>
                <th class="col-cpf">CPF</th>
                <th class="col-nota">Nota</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($candidates as $candidate)
                <tr>
                    <td class="col-protocol">{{ $candidate['numero_protocolo'] }}</td>
                    <td class="col-name">{{ $candidate['nome_completo'] }}</td>
                    <td class="col-cpf">{{ $candidate['cpf_mascarado'] }}</td>
                    <td class="col-nota">{{ number_format((float) $candidate['nota'], 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align: center; color: #64748b; white-space: normal;">
                        Nenhum candidato avaliado encontrado.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
