<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Tiempos de Resolución</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; }
        h1 { color: #1a56db; border-bottom: 2px solid #1a56db; padding-bottom: 5px; }
        h2 { color: #374151; font-size: 14px; margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 6px 8px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        th { background-color: #f3f4f6; font-weight: bold; }
        .header-info { margin: 10px 0; color: #6b7280; font-size: 11px; }
        .grid-3 { display: flex; flex-wrap: wrap; gap: 10px; }
        .card { border: 1px solid #e5e7eb; border-radius: 5px; padding: 10px; flex: 1; min-width: 100px; text-align: center; }
        .card .num { font-size: 18px; font-weight: bold; color: #1a56db; }
        .card .label { font-size: 10px; color: #6b7280; margin-top: 3px; }
        .footer { margin-top: 20px; font-size: 10px; color: #9ca3af; text-align: center; border-top: 1px solid #e5e7eb; padding-top: 8px; }
    </style>
</head>
<body>
    <h1>Tiempos de Resolución</h1>
    <div class="header-info">
        Período: {{ $from }} — {{ $to }} |
        Generado: {{ now()->format('d/m/Y H:i') }}
    </div>

    <div class="grid-3">
        <div class="card">
            <div class="num">{{ $issues_resolved }}</div>
            <div class="label">Incidencias Resueltas</div>
        </div>
        <div class="card">
            <div class="num">{{ $avg_hours ?? 'N/A' }}</div>
            <div class="label">Promedio (hrs)</div>
        </div>
        <div class="card">
            <div class="num">{{ $min_hours ?? 'N/A' }}</div>
            <div class="label">Mínimo (hrs)</div>
        </div>
        <div class="card">
            <div class="num">{{ $max_hours ?? 'N/A' }}</div>
            <div class="label">Máximo (hrs)</div>
        </div>
    </div>

    <h2>Desglose por Trabajador</h2>
    <table>
        <thead>
            <tr><th>Trabajador</th><th>Incidencias Resueltas</th><th>Tiempo Prom. (hrs)</th></tr>
        </thead>
        <tbody>
            @forelse ($by_worker as $item)
                <tr>
                    <td>{{ $item['worker']['first_name'] }} {{ $item['worker']['last_name'] }}</td>
                    <td>{{ $item['issues_resolved'] }}</td>
                    <td>{{ $item['avg_resolution_time_hours'] ?? 'N/A' }}</td>
                </tr>
            @empty
                <tr><td colspan="3">Sin datos</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">CityFix — Reporte generado automáticamente</div>
</body>
</html>
