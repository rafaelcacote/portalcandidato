<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>@yield('title', 'Documento')</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11pt;
            color: #1e293b;
            line-height: 1.5;
            margin: 0;
            padding: 32px 40px;
        }
        .header {
            border-bottom: 2px solid #0f766e;
            padding-bottom: 16px;
            margin-bottom: 24px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td {
            vertical-align: middle;
            padding: 0;
        }
        .header-logo-cell {
            width: 140px;
            padding-right: 16px;
        }
        .header-logo {
            display: block;
            height: 52px;
            width: auto;
            max-width: 140px;
        }
        .header h1 {
            font-size: 16pt;
            margin: 0 0 4px;
            color: #0f766e;
        }
        .header .institution {
            font-size: 10pt;
            color: #475569;
        }
        .meta {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .meta td {
            padding: 8px 10px;
            border: 1px solid #cbd5e1;
            vertical-align: top;
        }
        .meta td.label {
            width: 32%;
            font-weight: bold;
            background: #f8fafc;
        }
        .body-text { margin: 16px 0; text-align: justify; }
        .footer {
            margin-top: 40px;
            padding-top: 16px;
            border-top: 1px solid #cbd5e1;
            font-size: 9pt;
            color: #64748b;
        }
        .signature {
            margin-top: 48px;
            text-align: center;
        }
        .signature .line {
            border-top: 1px solid #334155;
            width: 280px;
            margin: 48px auto 8px;
        }
        @stack('styles')
    </style>
</head>
<body>
    <div class="header">
        <table class="header-table">
            <tr>
                @if (! empty($logoDataUri))
                    <td class="header-logo-cell">
                        <img
                            src="{{ $logoDataUri }}"
                            alt="PROENS"
                            class="header-logo"
                        />
                    </td>
                @endif
                <td>
                    <div class="institution">{{ $institution }}</div>
                    <h1>@yield('document-title')</h1>
                    <div style="font-size: 9pt; color: #64748b;">Processo seletivo — documento para fins profissionais</div>
                </td>
            </tr>
        </table>
    </div>

    @yield('content')

    <div class="footer">
        Documento gerado eletronicamente em {{ $generatedAt }} pelo Portal do Candidato.
        A autenticidade pode ser verificada pelo protocolo informado.
    </div>
</body>
</html>
