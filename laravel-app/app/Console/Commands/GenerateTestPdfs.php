<?php

namespace App\Console\Commands;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;

class GenerateTestPdfs extends Command
{
    protected $signature = 'generate:test-pdfs';
    protected $description = 'Genera PDFs de prueba para verificar los reportes';

    public function handle()
    {
        $from = '2026-04-01';
        $to = '2026-05-18';

        $this->info('Generando PDFs de prueba...');

        $byStatus = [
            ['status' => 'Pendiente', 'total' => 15],
            ['status' => 'En Progreso', 'total' => 23],
            ['status' => 'Resuelto', 'total' => 42],
            ['status' => 'Cerrado', 'total' => 8],
        ];

        $byCategoryData = [
            [
                'category' => 'Alumbrado Público', 'total' => 25,
                'by_status' => [
                    ['status' => 'Pendiente', 'total' => 5],
                    ['status' => 'En Progreso', 'total' => 8],
                    ['status' => 'Resuelto', 'total' => 10],
                    ['status' => 'Cerrado', 'total' => 2],
                ],
                'resolved_count' => 10, 'avg_resolution_time_hours' => 48.5,
            ],
            [
                'category' => 'Baches', 'total' => 30,
                'by_status' => [
                    ['status' => 'Pendiente', 'total' => 8],
                    ['status' => 'En Progreso', 'total' => 10],
                    ['status' => 'Resuelto', 'total' => 10],
                    ['status' => 'Cerrado', 'total' => 2],
                ],
                'resolved_count' => 10, 'avg_resolution_time_hours' => 72.0,
            ],
            [
                'category' => 'Recolección de Basura', 'total' => 18,
                'by_status' => [
                    ['status' => 'Pendiente', 'total' => 2],
                    ['status' => 'En Progreso', 'total' => 5],
                    ['status' => 'Resuelto', 'total' => 10],
                    ['status' => 'Cerrado', 'total' => 1],
                ],
                'resolved_count' => 10, 'avg_resolution_time_hours' => 24.0,
            ],
            [
                'category' => 'Áreas Verdes', 'total' => 15,
                'by_status' => [
                    ['status' => 'Pendiente', 'total' => 3],
                    ['status' => 'En Progreso', 'total' => 5],
                    ['status' => 'Resuelto', 'total' => 5],
                    ['status' => 'Cerrado', 'total' => 2],
                ],
                'resolved_count' => 5, 'avg_resolution_time_hours' => 96.0,
            ],
        ];

        $byWorkerData = [
            [
                'worker' => ['id' => 1, 'first_name' => 'Carlos', 'last_name' => 'Mendoza', 'email' => 'carlos@cityfix.com'],
                'total_assigned' => 15, 'completed_count' => 12, 'issues_resolved' => 10,
                'categories_worked' => [
                    ['category' => 'Alumbrado Público', 'total' => 8],
                    ['category' => 'Baches', 'total' => 7],
                ],
                'avg_completion_time_hours' => 36.5,
            ],
            [
                'worker' => ['id' => 2, 'first_name' => 'María', 'last_name' => 'García', 'email' => 'maria@cityfix.com'],
                'total_assigned' => 20, 'completed_count' => 18, 'issues_resolved' => 15,
                'categories_worked' => [
                    ['category' => 'Recolección de Basura', 'total' => 10],
                    ['category' => 'Áreas Verdes', 'total' => 10],
                ],
                'avg_completion_time_hours' => 28.0,
            ],
            [
                'worker' => ['id' => 3, 'first_name' => 'José', 'last_name' => 'López', 'email' => 'jose@cityfix.com'],
                'total_assigned' => 10, 'completed_count' => 8, 'issues_resolved' => 7,
                'categories_worked' => [
                    ['category' => 'Baches', 'total' => 5],
                    ['category' => 'Alumbrado Público', 'total' => 5],
                ],
                'avg_completion_time_hours' => 52.0,
            ],
        ];

        $byWorkerRes = [
            [
                'worker' => ['id' => 1, 'first_name' => 'Carlos', 'last_name' => 'Mendoza', 'email' => 'carlos@cityfix.com'],
                'issues_resolved' => 10, 'avg_resolution_time_hours' => 36.5,
            ],
            [
                'worker' => ['id' => 2, 'first_name' => 'María', 'last_name' => 'García', 'email' => 'maria@cityfix.com'],
                'issues_resolved' => 15, 'avg_resolution_time_hours' => 28.0,
            ],
            [
                'worker' => ['id' => 3, 'first_name' => 'José', 'last_name' => 'López', 'email' => 'jose@cityfix.com'],
                'issues_resolved' => 7, 'avg_resolution_time_hours' => 52.0,
            ],
        ];

        $detailsIssues = [];
        for ($i = 1; $i <= 25; $i++) {
            $categories = ['Alumbrado Público', 'Baches', 'Recolección de Basura', 'Áreas Verdes'];
            $statuses = ['Pendiente', 'En Progreso', 'Resuelto', 'Cerrado'];
            $detailsIssues[] = [
                'id' => $i,
                'title' => "Incidencia de prueba #{$i}",
                'category' => $categories[array_rand($categories)],
                'status' => $statuses[array_rand($statuses)],
                'created_by' => 'Usuario ' . rand(1, 10),
                'created_at' => date('Y-m-d H:i:s', strtotime("-{$i} days")),
                'resolution_time_hours' => rand(0, 1) ? round(rand(10, 200) + rand(0, 99) / 100, 2) : null,
                'assigned_worker' => rand(0, 1) ? $byWorkerData[array_rand($byWorkerData)]['worker'] : null,
                'upvotes_count' => rand(0, 20),
                'comments_count' => rand(0, 10),
                'location' => 'Calle ' . rand(1, 100) . ', Ciudad',
            ];
        }

        // 1. Summary
        $this->line('  Generando test-resumen.pdf...');
        Pdf::loadView('reports.pdf.summary', [
            'from' => $from, 'to' => $to,
            'total_issues' => 88,
            'by_status' => $byStatus,
            'by_category' => $byCategoryData,
            'total_upvotes' => 345,
            'total_comments' => 189,
            'total_workers_assigned' => 12,
            'avg_resolution_time_hours' => 52.3,
        ])->save(storage_path('app/test-resumen.pdf'));

        // 2. By Category
        $this->line('  Generando test-por-categoria.pdf...');
        Pdf::loadView('reports.pdf.by-category', [
            'from' => $from, 'to' => $to, 'data' => $byCategoryData,
        ])->save(storage_path('app/test-por-categoria.pdf'));

        // 3. By Worker
        $this->line('  Generando test-por-trabajador.pdf...');
        Pdf::loadView('reports.pdf.by-worker', [
            'from' => $from, 'to' => $to, 'data' => $byWorkerData,
        ])->save(storage_path('app/test-por-trabajador.pdf'));

        // 4. By Date
        $this->line('  Generando test-por-fecha.pdf...');
        Pdf::loadView('reports.pdf.by-date', [
            'from' => $from, 'to' => $to, 'group_by' => 'day',
            'created' => collect([
                (object)['period' => '2026-05-01', 'total' => 5],
                (object)['period' => '2026-05-02', 'total' => 3],
                (object)['period' => '2026-05-03', 'total' => 7],
                (object)['period' => '2026-05-04', 'total' => 2],
                (object)['period' => '2026-05-05', 'total' => 8],
                (object)['period' => '2026-05-06', 'total' => 4],
                (object)['period' => '2026-05-07', 'total' => 6],
            ]),
            'resolved' => collect([
                (object)['period' => '2026-05-01', 'total' => 2],
                (object)['period' => '2026-05-02', 'total' => 4],
                (object)['period' => '2026-05-03', 'total' => 3],
                (object)['period' => '2026-05-04', 'total' => 5],
                (object)['period' => '2026-05-05', 'total' => 6],
                (object)['period' => '2026-05-06', 'total' => 1],
                (object)['period' => '2026-05-07', 'total' => 4],
            ]),
        ])->save(storage_path('app/test-por-fecha.pdf'));

        // 5. Resolution Times
        $this->line('  Generando test-tiempos-resolucion.pdf...');
        Pdf::loadView('reports.pdf.resolution-times', [
            'from' => $from, 'to' => $to,
            'issues_resolved' => 42,
            'avg_hours' => 52.3,
            'min_hours' => 2.5,
            'max_hours' => 340.0,
            'by_worker' => $byWorkerRes,
        ])->save(storage_path('app/test-tiempos-resolucion.pdf'));

        // 6. Details
        $this->line('  Generando test-detalle-incidencias.pdf...');
        Pdf::loadView('reports.pdf.details', [
            'from' => $from, 'to' => $to, 'issues' => $detailsIssues,
        ])->save(storage_path('app/test-detalle-incidencias.pdf'));

        $this->newLine();
        $this->info('PDFs generados en storage/app/');
    }
}
