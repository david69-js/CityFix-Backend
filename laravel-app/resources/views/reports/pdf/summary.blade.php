<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte Resumen</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; }
        h1 { color: #1a56db; border-bottom: 2px solid #1a56db; padding-bottom: 5px; }
        h2 { color: #374151; font-size: 14px; margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 6px 8px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        th { background-color: #f3f4f6; font-weight: bold; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 11px; }
        .header-info { margin: 10px 0; color: #6b7280; font-size: 11px; }
        .grid-2 { display: flex; flex-wrap: wrap; gap: 10px; }
        .card { border: 1px solid #e5e7eb; border-radius: 5px; padding: 10px; flex: 1; min-width: 120px; text-align: center; }
        .card .num { font-size: 20px; font-weight: bold; color: #1a56db; }
        .card .label { font-size: 10px; color: #6b7280; margin-top: 3px; }
        .footer { margin-top: 20px; font-size: 10px; color: #9ca3af; text-align: center; border-top: 1px solid #e5e7eb; padding-top: 8px; }
    </style>
</head>
<body>
    <h1>Reporte Resumen</h1>
    <div class="header-info">
        Período: {{ $from }} — {{ $to }} |
        Generado: {{ now()->format('d/m/Y H:i') }}
    </div>

    <div class="grid-2">
        <div class="card">
            <div class="num">{{ $total_issues }}</div>
            <div class="label">Incidencias Totales</div>
        </div>
        <div class="card">
            <div class="num">{{ $total_upvotes }}</div>
            <div class="label">Votos Totales</div>
        </div>
        <div class="card">
            <div class="num">{{ $total_comments }}</div>
            <div class="label">Comentarios</div>
        </div>
        <div class="card">
            <div class="num">{{ $total_workers_assigned }}</div>
            <div class="label">Trabajadores Asignados</div>
        </div>
        <div class="card">
            <div class="num">{{ $avg_resolution_time_hours ?? 'N/A' }}</div>
            <div class="label">Tiempo Prom. Resolución (hrs)</div>
        </div>
    </div>

    <h2>Incidencias por Estado</h2>
    <table>
        <thead>
            <tr><th>Estado</th><th>Total</th></tr>
        </thead>
        <tbody>
            @foreach ($by_status as $item)
                <tr><td>{{ $item['status'] }}</td><td>{{ $item['total'] }}</td></tr>
            @endforeach
        </tbody>
    </table>

    <h2>Incidencias por Categoría</h2>
    <table>
        <thead>
            <tr><th>Categoría</th><th>Total</th></tr>
        </thead>
        <tbody>
            @foreach ($by_category as $item)
                <tr><td>{{ $item['category'] }}</td><td>{{ $item['total'] }}</td></tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">CityFix — Reporte generado automáticamente</div>
</body>
</html>
