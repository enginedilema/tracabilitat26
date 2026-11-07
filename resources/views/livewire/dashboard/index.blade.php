<div wire:poll.60s class="flex w-full flex-col gap-6 pb-8">
    {{-- Capçalera --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-700 dark:text-emerald-400">Traçabilitat · Administració</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-zinc-950 md:text-3xl dark:text-white">Centre de control</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Indicadors de mostres, activitat i funcionament del sistema.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-xs text-zinc-500 dark:text-zinc-400">Actualitzat: {{ $resum['actualitzat'] }}</span>
            <flux:button size="sm" variant="outline" wire:click="$refresh" wire:loading.attr="disabled">Actualitzar</flux:button>
        </div>
    </div>

    @if ($resum['missatge'])
        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200" role="status">
            {{ $resum['missatge'] }}
        </div>
    @endif

    {{-- Indicadors principals; NULL vol dir informació no disponible, no pas zero. --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-start justify-between gap-2">
                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Mostres totals</p>
                <span class="rounded-lg bg-zinc-100 px-2 py-1 text-xs font-semibold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">Actives</span>
            </div>
            <p class="mt-3 text-4xl font-semibold tabular-nums tracking-tight text-zinc-950 dark:text-white">{{ $resum['total'] ?? '—' }}</p>
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">No inclou les baixes lògiques</p>
        </div>
        <div class="rounded-2xl border border-amber-200 bg-white p-5 shadow-sm dark:border-amber-900/60 dark:bg-zinc-900">
            <div class="flex items-start justify-between gap-2">
                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Pendents de recepció</p>
                <span class="h-2.5 w-2.5 rounded-full bg-amber-500" aria-hidden="true"></span>
            </div>
            <p class="mt-3 text-4xl font-semibold tabular-nums tracking-tight text-amber-700 dark:text-amber-400">{{ $resum['pendents'] ?? '—' }}</p>
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">Mostres que requereixen confirmació</p>
        </div>
        <div class="rounded-2xl border border-violet-200 bg-white p-5 shadow-sm dark:border-violet-900/60 dark:bg-zinc-900">
            <div class="flex items-start justify-between gap-2">
                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">En processament</p>
                <span class="h-2.5 w-2.5 rounded-full bg-violet-500" aria-hidden="true"></span>
            </div>
            <p class="mt-3 text-4xl font-semibold tabular-nums tracking-tight text-violet-700 dark:text-violet-400">{{ $resum['enProces'] ?? '—' }}</p>
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">Circuits en curs</p>
        </div>
        <div class="rounded-2xl border border-emerald-200 bg-white p-5 shadow-sm dark:border-emerald-900/60 dark:bg-zinc-900">
            <div class="flex items-start justify-between gap-2">
                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">Finalitzades</p>
                <span class="h-2.5 w-2.5 rounded-full bg-emerald-500" aria-hidden="true"></span>
            </div>
            <p class="mt-3 text-4xl font-semibold tabular-nums tracking-tight text-emerald-700 dark:text-emerald-400">{{ $resum['finalitzades'] ?? '—' }}</p>
            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">Pendents d'arxiu o tancament</p>
        </div>
    </div>

    {{-- Resum ràpid --}}
    <div class="flex flex-wrap items-center gap-x-5 gap-y-2 rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm dark:border-zinc-700 dark:bg-zinc-900/70">
        <span><span class="font-semibold tabular-nums">{{ $resum['avui'] ?? '—' }}</span> <span class="text-zinc-500 dark:text-zinc-400">entrades avui</span></span>
        <span class="hidden h-4 w-px bg-zinc-200 sm:block dark:bg-zinc-700" aria-hidden="true"></span>
        <span><span class="font-semibold tabular-nums">{{ $resum['citologies'] ?? '—' }}</span> <span class="text-zinc-500 dark:text-zinc-400">citologies</span></span>
        <span class="hidden h-4 w-px bg-zinc-200 sm:block dark:bg-zinc-700" aria-hidden="true"></span>
        <span><span class="font-semibold tabular-nums">{{ $resum['biopsies'] ?? '—' }}</span> <span class="text-zinc-500 dark:text-zinc-400">biòpsies</span></span>
        <span class="hidden h-4 w-px bg-zinc-200 sm:block dark:bg-zinc-700" aria-hidden="true"></span>
        <span><span class="font-semibold tabular-nums">{{ $resum['arxivades'] ?? '—' }}</span> <span class="text-zinc-500 dark:text-zinc-400">arxivades</span></span>
    </div>

    <div class="grid gap-5 xl:grid-cols-12">
        <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm xl:col-span-7 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-zinc-950 dark:text-white">Estat dels circuits</h2>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Distribució de les mostres actuals</p>
                </div>
                <a href="{{ route('mostres.index') }}" wire:navigate class="text-sm font-medium text-emerald-700 hover:underline dark:text-emerald-400">Veure mostres</a>
            </div>
            <div class="mt-6 space-y-4">
                @foreach ($resum['estats'] as $estat)
                    <div>
                        <div class="mb-1.5 flex items-center justify-between gap-3 text-sm">
                            <div class="flex items-center gap-2">
                                <span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $estat['color'] }}" aria-hidden="true"></span>
                                <span class="text-zinc-700 dark:text-zinc-300">{{ $estat['nom'] }}</span>
                            </div>
                            <span class="font-semibold tabular-nums text-zinc-950 dark:text-white">{{ $estat['quantitat'] }} <span class="font-normal text-zinc-400">· {{ $estat['percentatge'] }}%</span></span>
                        </div>
                        <div class="h-2.5 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800" role="progressbar" aria-label="{{ $estat['nom'] }}" aria-valuenow="{{ $estat['percentatge'] }}" aria-valuemin="0" aria-valuemax="100">
                            <div class="h-full rounded-full" style="width: {{ $estat['percentatge'] }}%; background-color: {{ $estat['color'] }}"></div>
                        </div>
                    </div>
                @endforeach
                @if (count($resum['estats']) === 0)
                    <p class="text-sm text-zinc-500">Encara no hi ha dades disponibles.</p>
                @endif
            </div>
            <div class="mt-6 border-t border-zinc-100 pt-5 dark:border-zinc-800">
                <h3 class="mb-3 text-sm font-semibold text-zinc-800 dark:text-zinc-200">Tipus de mostres</h3>
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($resum['tipus'] as $tipus)
                        <div class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-800/60">
                            <div class="mb-2 flex items-center justify-between text-sm">
                                <span class="text-zinc-600 dark:text-zinc-300">{{ $tipus['nom'] }}</span>
                                <span class="font-semibold tabular-nums">{{ $tipus['quantitat'] }}</span>
                            </div>
                            <div class="h-1.5 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                                <div class="h-full rounded-full" style="width: {{ ($resum['total'] ?? 0) > 0 ? round($tipus['quantitat'] * 100 / $resum['total'], 1) : 0 }}%; background-color: {{ $tipus['color'] }}"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm xl:col-span-5 dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="text-base font-semibold text-zinc-950 dark:text-white">Estat del sistema</h2>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Indicadors tècnics verificats a l'aplicació</p>
            <div class="mt-5 divide-y divide-zinc-100 dark:divide-zinc-800">
                <div class="flex items-center justify-between gap-2 py-3">
                    <div>
                        <p class="text-sm font-medium">Base de dades</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Consulta de connexió</p>
                    </div>
                    @if ($resum['sistema']['baseDadesOperativa'])
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>Operativa</span>
                    @else
                        <span class="rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700 dark:bg-rose-900/30 dark:text-rose-300">{{ $resum['sistema']['baseDadesEtiqueta'] }}</span>
                    @endif
                </div>
                <div class="flex items-center justify-between gap-2 py-3">
                    <span class="text-sm">Usuaris registrats</span>
                    <span class="font-semibold tabular-nums">{{ $resum['sistema']['usuaris'] ?? '—' }}</span>
                </div>
                <div class="flex items-center justify-between gap-2 py-3">
                    <span class="text-sm">Accions en les últimes 24 h</span>
                    <span class="font-semibold tabular-nums">{{ $resum['sistema']['accions24h'] ?? '—' }}</span>
                </div>
                <div class="flex items-center justify-between gap-2 py-3">
                    <div>
                        <p class="text-sm">Cua de tasques</p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $resum['sistema']['cuaEtiqueta'] }}</p>
                    </div>
                    <span class="font-semibold tabular-nums">{{ $resum['sistema']['feinesPendents'] ?? '—' }}</span>
                </div>
                <div class="flex items-center justify-between gap-2 py-3">
                    <span class="text-sm">Tasques fallides registrades</span>
                    <span class="font-semibold tabular-nums {{ ($resum['sistema']['feinesFallides'] ?? 0) > 0 ? 'text-rose-600 dark:text-rose-400' : '' }}">{{ $resum['sistema']['feinesFallides'] ?? '—' }}</span>
                </div>
                <div class="flex items-center justify-between gap-2 py-3">
                    <span class="text-sm">Entorn</span>
                    <span class="rounded-md bg-zinc-100 px-2 py-1 text-xs font-semibold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">{{ $resum['sistema']['entorn'] }}</span>
                </div>
            </div>
            <p class="mt-4 text-xs leading-relaxed text-zinc-500 dark:text-zinc-400">Versió Laravel {{ $resum['sistema']['laravel'] }} · PHP {{ $resum['sistema']['php'] }}. El símbol «—» indica que l'indicador no es pot consultar amb la configuració actual.</p>
        </section>
    </div>

    <div class="grid gap-5 xl:grid-cols-12">
        <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm xl:col-span-5 dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="text-base font-semibold text-zinc-950 dark:text-white">Entrada de mostres · 7 dies</h2>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Segons la data d'entrada registrada</p>
            <div class="mt-5 flex h-40 items-end gap-2" aria-label="Gràfic de mostres rebudes els últims set dies">
                @foreach ($resum['setmana'] as $dia)
                    <div class="flex h-full min-w-0 flex-1 flex-col items-center justify-end gap-2" title="{{ $dia['data'] }}: {{ $dia['quantitat'] }} mostres">
                        <span class="text-xs font-semibold tabular-nums text-zinc-700 dark:text-zinc-200">{{ $dia['quantitat'] }}</span>
                        <div class="flex h-28 w-full items-end rounded-md bg-zinc-50 dark:bg-zinc-800/60">
                            <div class="w-full rounded-t-md bg-emerald-500/85 dark:bg-emerald-400/80" style="height: {{ $dia['altura'] }}%"></div>
                        </div>
                        <span class="text-[11px] tabular-nums text-zinc-500 dark:text-zinc-400">{{ $dia['data'] }}</span>
                    </div>
                @endforeach
                @if (count($resum['setmana']) === 0)
                    <p class="self-center text-sm text-zinc-500">No hi ha dades disponibles.</p>
                @endif
            </div>
        </section>

        <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm xl:col-span-7 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-zinc-950 dark:text-white">Mostres recents</h2>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Progrés basat en les fases confirmades</p>
                </div>
                <a href="{{ route('mostres.index') }}" wire:navigate class="text-sm font-medium text-emerald-700 hover:underline dark:text-emerald-400">Totes</a>
            </div>
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-zinc-100 text-xs text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                        <tr><th class="py-3 pr-4 font-medium">Mostra</th><th class="py-3 pr-4 font-medium">Tipus / estat</th><th class="py-3 pr-4 font-medium">Progrés</th><th class="py-3 text-right font-medium">Circuit</th></tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse ($resum['recents'] as $mostra)
                            <tr>
                                <td class="py-3 pr-4"><span class="font-semibold text-zinc-900 dark:text-white">{{ $mostra['identificacio'] }}</span><p class="text-xs text-zinc-500">{{ $mostra['data'] }}</p></td>
                                <td class="py-3 pr-4"><span class="text-zinc-700 dark:text-zinc-200">{{ $mostra['tipus'] }}</span><p class="text-xs text-zinc-500">{{ $mostra['estat'] }}</p></td>
                                <td class="min-w-28 py-3 pr-4">
                                    <div class="mb-1 flex justify-between text-xs tabular-nums"><span>{{ $mostra['percentatge'] }}%</span><span class="text-zinc-400">{{ $mostra['fases'] }}</span></div>
                                    <div class="h-1.5 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800" role="progressbar" aria-label="Progrés de la mostra {{ $mostra['identificacio'] }}" aria-valuenow="{{ $mostra['percentatge'] }}" aria-valuemin="0" aria-valuemax="100">
                                        <div class="h-full rounded-full bg-emerald-500" style="width: {{ $mostra['percentatge'] }}%"></div>
                                    </div>
                                </td>
                                <td class="py-3 text-right"><a href="{{ route('mostres.proces', $mostra['id']) }}" wire:navigate class="font-medium text-emerald-700 hover:underline dark:text-emerald-400">Obrir</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-8 text-center text-zinc-500">Encara no hi ha mostres registrades.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <h2 class="text-base font-semibold text-zinc-950 dark:text-white">Activitat recent</h2>
        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Últims moviments registrats a la traçabilitat</p>
        <div class="mt-4 grid gap-2 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($resum['activitat'] as $event)
                <div class="rounded-xl border border-zinc-100 bg-zinc-50/60 p-4 dark:border-zinc-800 dark:bg-zinc-800/30">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <span class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $event['accio'] }}</span>
                        <span class="text-xs tabular-nums text-zinc-500">{{ $event['data'] }}</span>
                    </div>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ $event['usuari'] }}</p>
                    <p class="mt-1 text-xs text-zinc-500">Mostra: 
                        @if ($event['mostraId'])
                            <a href="{{ route('mostres.proces', $event['mostraId']) }}" wire:navigate class="font-medium text-emerald-700 hover:underline dark:text-emerald-400">{{ $event['mostra'] }}</a>
                        @else
                            {{ $event['mostra'] }}
                        @endif
                    </p>
                </div>
            @empty
                <p class="py-5 text-sm text-zinc-500">Encara no hi ha moviments registrats.</p>
            @endforelse
        </div>
    </section>
</div>
