<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte por Trabajador</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; }
        h1 { color: #1a56db; border-bottom: 2px solid #1a56db; padding-bottom: 5px; }
        h2 { color: #374151; font-size: 13px; margin-top: 18px; }
        table { width: 100%; border-collapse: collapse; margin: 8px 0; }
        th, td { padding: 5px 7px; text-align: left; border-bottom: 1px solid #e5e7eb; font-size: 11px; }
        th { background-color: #f3f4f6; font-weight: bold; }
        .header-info { margin: 10px 0; color: #6b7280; font-size: 11px; }
        .worker-block { border: 1px solid #e5e7eb; border-radius: 5px; padding: 10px; margin-bottom: 12px; }
        .worker-name { font-weight: bold; color: #1a56db; font-size: 13px; }
        .footer { margin-top: 20px; font-size: 10px; color: #9ca3af; text-align: center; border-top: 1px solid #e5e7eb; padding-top: 8px; }
    </style>
</head>
<body>
    <h1>Reporte por Trabajador</h1>
    <div class="header-info">
        Período: {{ $from }} — {{ $to }} |
        Generado: {{ now()->format('d/m/Y H:i') }}
    </div>

    @forelse ($data as $item)
        <div class="worker-block">
            <div class="worker-name">{{ $item['worker']['first_name'] }} {{ $item['worker']['last_name'] }}</div>
            <div style="color: #6b7280; font-size: 11px;">{{ $item['worker']['email'] }}</div>

            <table>
                <tr><th>Asignadas</th><td>{{ $item['total_assigned'] }}</td></tr>
                <tr><th>Completadas</th><td>{{ $item['completed_count'] }}</td></tr>
                <tr><th>Incidencias Resueltas</th><td>{{ $item['issues_resolved'] }}</td></tr>
                <tr><th>Tiempo Prom. Finalización</th><td>{{ $item['avg_completion_time_hours'] ?? 'N/A' }} hrs</td></tr>
            </table>

            @if (count($item['categories_worked']) > 0)
                <h2>Categorías Trabajadas</h2>
                <table>
                    <thead><tr><th>Categoría</th><th>Total</th></tr></thead>
                    <tbody>
                        @foreach ($item['categories_worked'] as $cat)
                            <tr><td>{{ $cat['category'] }}</td><td>{{ $cat['total'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @empty
        <p>No hay datos para el período seleccionado.</p>
    @endforelse

    <div class="footer">CityFix — Reporte generado automáticamente</div>
</body>
</html>
