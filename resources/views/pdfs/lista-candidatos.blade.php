@extends('pdfs.layout')

@section('title', 'Lista de candidatos')
@section('document-title', 'Lista de candidatos')

@section('content')
    <p style="margin: 0 0 8px;"><strong>Processo:</strong> {{ $process->titulo }}</p>
    <p style="margin: 0 0 24px;"><strong>Data:</strong> {{ $generatedAt }}</p>

    <table style="width: 100%; border-collapse: collapse; font-size: 10pt;">
        <thead>
            <tr>
                <th style="border: 1px solid #cbd5e1; padding: 8px 10px; background: #f8fafc; text-align: left; width: 22%;">
                    Cod. inscrição
                </th>
                <th style="border: 1px solid #cbd5e1; padding: 8px 10px; background: #f8fafc; text-align: left;">
                    Nome completo
                </th>
                <th style="border: 1px solid #cbd5e1; padding: 8px 10px; background: #f8fafc; text-align: left; width: 22%;">
                    CPF
                </th>
            </tr>
        </thead>
        <tbody>
            @forelse ($candidates as $candidate)
                <tr>
                    <td style="border: 1px solid #cbd5e1; padding: 8px 10px; vertical-align: top;">
                        {{ $candidate['numero_protocolo'] }}
                    </td>
                    <td style="border: 1px solid #cbd5e1; padding: 8px 10px; vertical-align: top;">
                        {{ $candidate['nome_completo'] }}
                    </td>
                    <td style="border: 1px solid #cbd5e1; padding: 8px 10px; vertical-align: top;">
                        {{ $candidate['cpf_mascarado'] }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="border: 1px solid #cbd5e1; padding: 16px 10px; text-align: center; color: #64748b;">
                        Nenhum candidato inscrito neste processo.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
