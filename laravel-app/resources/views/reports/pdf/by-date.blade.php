<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte por Fecha</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; }
        h1 { color: #1a56db; border-bottom: 2px solid #1a56db; padding-bottom: 5px; }
        h2 { color: #374151; font-size: 14px; margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 6px 8px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        th { background-color: #f3f4f6; font-weight: bold; }
        .header-info { margin: 10px 0; color: #6b7280; font-size: 11px; }
        .footer { margin-top: 20px; font-size: 10px; color: #9ca3af; text-align: center; border-top: 1px solid #e5e7eb; padding-top: 8px; }
    </style>
</head>
<body>
    <h1>Reporte por Fecha</h1>
    <div class="header-info">
        Período: {{ $from }} — {{ $to }} |
        Agrupado por: {{ $group_by }} |
        Generado: {{ now()->format('d/m/Y H:i') }}
    </div>

    <h2>Incidencias Creadas</h2>
    <table>
        <thead><tr><th>Período</th><th>Total</th></tr></thead>
        <tbody>
            @forelse ($created as $item)
                <tr><td>{{ $item->period }}</td><td>{{ $item->total }}</td></tr>
            @empty
                <tr><td colspan="2">Sin datos</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Incidencias Resueltas</h2>
    <table>
        <thead><tr><th>Período</th><th>Total</th></tr></thead>
        <tbody>
            @forelse ($resolved as $item)
                <tr><td>{{ $item->period }}</td><td>{{ $item->total }}</td></tr>
            @empty
                <tr><td colspan="2">Sin datos</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">CityFix — Reporte generado automáticamente</div>
</body>
</html>
