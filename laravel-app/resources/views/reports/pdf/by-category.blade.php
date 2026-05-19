<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte por Categoría</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; }
        h1 { color: #1a56db; border-bottom: 2px solid #1a56db; padding-bottom: 5px; }
        h2 { color: #374151; font-size: 14px; margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 6px 8px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        th { background-color: #f3f4f6; font-weight: bold; }
        .header-info { margin: 10px 0; color: #6b7280; font-size: 11px; }
        .cat-title { background: #f9fafb; font-weight: bold; }
        .footer { margin-top: 20px; font-size: 10px; color: #9ca3af; text-align: center; border-top: 1px solid #e5e7eb; padding-top: 8px; }
    </style>
</head>
<body>
    <h1>Reporte por Categoría</h1>
    <div class="header-info">
        Período: {{ $from }} — {{ $to }} |
        Generado: {{ now()->format('d/m/Y H:i') }}
    </div>

    @forelse ($data as $item)
        <h2>{{ $item['category'] }}</h2>
        <p>Total: {{ $item['total'] }} | Resueltos: {{ $item['resolved_count'] }} | Tiempo Prom.: {{ $item['avg_resolution_time_hours'] ?? 'N/A' }} hrs</p>
        <table>
            <thead>
                <tr><th>Estado</th><th>Total</th></tr>
            </thead>
            <tbody>
                @foreach ($item['by_status'] as $status)
                    <tr><td>{{ $status['status'] }}</td><td>{{ $status['total'] }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @empty
        <p>No hay datos para el período seleccionado.</p>
    @endforelse

    <div class="footer">CityFix — Reporte generado automáticamente</div>
</body>
</html>
