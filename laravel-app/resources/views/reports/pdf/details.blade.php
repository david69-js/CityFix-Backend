<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Detalle de Incidencias</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; color: #333; }
        h1 { color: #1a56db; border-bottom: 2px solid #1a56db; padding-bottom: 5px; font-size: 16px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 4px 6px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        th { background-color: #f3f4f6; font-weight: bold; font-size: 10px; }
        td { font-size: 9px; }
        .header-info { margin: 10px 0; color: #6b7280; font-size: 10px; }
        .footer { margin-top: 20px; font-size: 9px; color: #9ca3af; text-align: center; border-top: 1px solid #e5e7eb; padding-top: 6px; }
    </style>
</head>
<body>
    <h1>Detalle de Incidencias</h1>
    <div class="header-info">
        Período: {{ $from }} — {{ $to }} |
        Generado: {{ now()->format('d/m/Y H:i') }}
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Título</th>
                <th>Categoría</th>
                <th>Estado</th>
                <th>Creado por</th>
                <th>Fecha</th>
                <th>Tiempo Res. (hrs)</th>
                <th>Trabajador</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($issues as $issue)
                <tr>
                    <td>{{ $issue['id'] }}</td>
                    <td>{{ $issue['title'] }}</td>
                    <td>{{ $issue['category'] }}</td>
                    <td>{{ $issue['status'] }}</td>
                    <td>{{ $issue['created_by'] ?? 'N/A' }}</td>
                    <td>{{ \Carbon\Carbon::parse($issue['created_at'])->format('d/m/Y H:i') }}</td>
                    <td>{{ $issue['resolution_time_hours'] ?? 'N/A' }}</td>
                    <td>
                        @if ($issue['assigned_worker'])
                            {{ $issue['assigned_worker']['first_name'] }} {{ $issue['assigned_worker']['last_name'] }}
                        @else
                            N/A
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8">No hay datos para el período seleccionado.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">CityFix — Reporte generado automáticamente | Pág. 1 de 1</div>
</body>
</html>
