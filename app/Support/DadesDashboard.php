<?php

namespace App\Support;

use App\Models\Mostra;
use App\Models\MostraEsdeveniment;
use App\Models\MostraProcessament;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Resum de lectura per al dashboard. No modifica cap registre.
 * Les consultes de mostres exclouen les baixes lògiques.
 */
final class DadesDashboard
{
    private const ESTATS = [
        'pendent' => ['nom' => 'Pendent', 'color' => '#d97706'],
        'rebuda' => ['nom' => 'Rebuda', 'color' => '#0284c7'],
        'en_proces' => ['nom' => 'En procés', 'color' => '#7c3aed'],
        'finalitzada' => ['nom' => 'Finalitzada', 'color' => '#059669'],
        'arxivada' => ['nom' => 'Arxivada', 'color' => '#64748b'],
    ];

    /** @return array<string, mixed> */
    public function obtenir(): array
    {
        $sistema = [
            'baseDadesOperativa' => false,
            'baseDadesEtiqueta' => 'No disponible',
            'usuaris' => null,
            'accions24h' => null,
            'feinesPendents' => null,
            'feinesFallides' => null,
            'cuaEtiqueta' => 'No monitoritzada',
            'entorn' => app()->environment(),
            'php' => PHP_VERSION,
            'laravel' => app()->version(),
        ];

        $buit = [
            'total' => null,
            'avui' => null,
            'citologies' => null,
            'biopsies' => null,
            'pendents' => null,
            'enProces' => null,
            'finalitzades' => null,
            'arxivades' => null,
            'estats' => [],
            'tipus' => [],
            'recents' => [],
            'activitat' => [],
            'setmana' => [],
            'sistema' => $sistema,
            'actualitzat' => now()->format('d/m/Y H:i'),
            'missatge' => null,
        ];

        try {
            DB::select('SELECT 1');
            $sistema['baseDadesOperativa'] = true;
            $sistema['baseDadesEtiqueta'] = 'Operativa';

            if (! Schema::hasTable('mostres') || ! Schema::hasTable('mostra_processaments') || ! Schema::hasTable('mostra_esdeveniments')) {
                return array_replace($buit, [
                    'sistema' => $sistema,
                    'missatge' => 'Cal executar les migracions del circuit de mostres.',
                ]);
            }

            $perEstat = Mostra::query()
                ->select('estat', DB::raw('COUNT(*) AS total'))
                ->groupBy('estat')
                ->pluck('total', 'estat');

            $total = (int) $perEstat->sum();
            $estats = [];
            foreach (self::ESTATS as $clau => $detall) {
                $quantitat = (int) ($perEstat[$clau] ?? 0);
                $estats[] = [
                    'clau' => $clau,
                    'nom' => $detall['nom'],
                    'color' => $detall['color'],
                    'quantitat' => $quantitat,
                    'percentatge' => $total > 0 ? round($quantitat * 100 / $total, 1) : 0,
                ];
            }

            $perTipus = Mostra::query()
                ->select('tipus_mostra', DB::raw('COUNT(*) AS total'))
                ->groupBy('tipus_mostra')
                ->pluck('total', 'tipus_mostra');

            $tipus = [
                ['nom' => 'Citologies', 'quantitat' => (int) ($perTipus['citologia'] ?? 0), 'color' => '#0d9488'],
                ['nom' => 'Biòpsies', 'quantitat' => (int) ($perTipus['biopsia'] ?? 0), 'color' => '#6366f1'],
            ];

            $mostresRecents = Mostra::query()
                ->orderByDesc('data_entrada')
                ->orderByDesc('id')
                ->limit(6)
                ->get(['id', 'identificacio', 'tipus_mostra', 'estat', 'data_entrada', 'recepcio_confirmada']);

            $processaments = MostraProcessament::query()
                ->whereIn('mostra_id', $mostresRecents->pluck('id'))
                ->get()
                ->keyBy('mostra_id');

            $recents = [];
            foreach ($mostresRecents as $mostra) {
                $progres = ProgresMostra::calcular($mostra, $processaments->get($mostra->id));

                $recents[] = [
                    'id' => $mostra->id,
                    'identificacio' => $mostra->identificacio,
                    'tipus' => $mostra->tipus_mostra === 'citologia' ? 'Citologia' : 'Biòpsia',
                    'estat' => self::ESTATS[$mostra->estat]['nom'] ?? ucfirst((string) $mostra->estat),
                    'data' => $mostra->data_entrada?->format('d/m/Y') ?? '—',
                    'percentatge' => $progres['percentatge'],
                    'fases' => $progres['completades'].'/'.$progres['total'],
                ];
            }

            $esdeveniments = MostraEsdeveniment::query()
                ->with('mostra:id,identificacio')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit(6)
                ->get();

            $activitat = [];
            foreach ($esdeveniments as $e) {
                $noms = [
                    'mostra_registrada' => 'Mostra registrada',
                    'mostra_actualitzada' => 'Mostra modificada',
                    'processament_actualitzat' => 'Processament modificat',
                    'mostra_baixa' => 'Baixa de mostra',
                ];

                $activitat[] = [
                    'accio' => $noms[$e->accio] ?? str_replace('_', ' ', ucfirst($e->accio)),
                    'usuari' => $e->usuari_nom ?: 'Usuari no identificat',
                    'mostra' => $e->mostra->identificacio ?? 'Mostra de baixa',
                    'mostraId' => $e->mostra?->id,
                    'data' => $e->created_at?->format('d/m/Y H:i') ?? '—',
                ];
            }

            $dies = Mostra::query()
                ->selectRaw('DATE(data_entrada) AS dia, COUNT(*) AS total')
                ->whereDate('data_entrada', '>=', today()->subDays(6))
                ->groupBy(DB::raw('DATE(data_entrada)'))
                ->pluck('total', 'dia');

            $setmana = [];
            $maxim = max(1, (int) $dies->max());
            for ($i = 6; $i >= 0; $i--) {
                $dia = today()->subDays($i);
                $quantitat = (int) ($dies[$dia->format('Y-m-d')] ?? 0);
                $setmana[] = [
                    'data' => $dia->format('d/m'),
                    'quantitat' => $quantitat,
                    'altura' => max(2, (int) round(100 * $quantitat / $maxim)),
                ];
            }

            $sistema['usuaris'] = User::query()->count();
            $sistema['accions24h'] = MostraEsdeveniment::query()
                ->where('created_at', '>=', now()->subHours(24))
                ->count();

            $this->carregarEstatCua($sistema);

            return [
                'total' => $total,
                'avui' => Mostra::query()->whereDate('data_entrada', today())->count(),
                'citologies' => (int) ($perTipus['citologia'] ?? 0),
                'biopsies' => (int) ($perTipus['biopsia'] ?? 0),
                'pendents' => (int) ($perEstat['pendent'] ?? 0),
                'enProces' => (int) ($perEstat['en_proces'] ?? 0),
                'finalitzades' => (int) ($perEstat['finalitzada'] ?? 0),
                'arxivades' => (int) ($perEstat['arxivada'] ?? 0),
                'estats' => $estats,
                'tipus' => $tipus,
                'recents' => $recents,
                'activitat' => $activitat,
                'setmana' => $setmana,
                'sistema' => $sistema,
                'actualitzat' => now()->format('d/m/Y H:i'),
                'missatge' => null,
            ];
        } catch (Throwable $e) {
            // No mostrem missatges SQL ni detalls d'infraestructura a la interfície.
            report($e);

            return array_replace($buit, [
                'sistema' => array_replace($sistema, [
                    'baseDadesOperativa' => false,
                    'baseDadesEtiqueta' => 'Error de consulta',
                ]),
                'missatge' => 'No es poden carregar els indicadors. Consulta el registre de l’aplicació.',
            ]);
        }
    }

    /** @param array<string, mixed> $sistema */
    private function carregarEstatCua(array &$sistema): void
    {
        $connexio = (string) config('queue.default', 'sync');
        $driver = (string) config("queue.connections.{$connexio}.driver", $connexio);

        if ($driver === 'sync') {
            $sistema['cuaEtiqueta'] = 'Execució immediata';
        } elseif ($driver === 'database') {
            $connexioDb = config("queue.connections.{$connexio}.connection");
            if ($connexioDb === null || $connexioDb === config('database.default')) {
                $taula = (string) config("queue.connections.{$connexio}.table", 'jobs');
                if (Schema::hasTable($taula)) {
                    $sistema['feinesPendents'] = DB::table($taula)->count();
                    $sistema['cuaEtiqueta'] = 'Cua de base de dades';
                }
            }
        }

        $taulaFallides = (string) config('queue.failed.table', 'failed_jobs');
        if ($taulaFallides !== '' && Schema::hasTable($taulaFallides)) {
            $sistema['feinesFallides'] = DB::table($taulaFallides)->count();
        }
    }
}
