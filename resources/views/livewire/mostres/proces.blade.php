<div class="mx-auto flex w-full max-w-6xl flex-col gap-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <a href="{{ route('mostres.index') }}" wire:navigate class="text-sm text-zinc-600 hover:underline dark:text-zinc-400">← Tornar a mostres</a>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight">Circuit de processament</h1>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                Mostra <strong>{{ $mostra->identificacio }}</strong> ·
                {{ $mostra->tipus_mostra === 'citologia' ? 'Citologia' : 'Biòpsia' }} ·
                Entrada {{ $mostra->data_entrada?->format('d/m/Y') }}
            </p>
        </div>
        <span class="rounded-full border border-zinc-300 px-3 py-1 text-sm font-semibold dark:border-zinc-600">
            @switch($mostra->estat)
                @case('pendent') Pendent de recepció @break
                @case('rebuda') Rebuda @break
                @case('en_proces') En procés @break
                @case('finalitzada') Finalitzada @break
                @case('arxivada') Arxivada @break
                @default {{ $mostra->estat }}
            @endswitch
        </span>
    </div>

    {{-- Progrés del circuit: només confirmacions guardades, no edicions pendents. --}}
    <section aria-labelledby="titol-progres" class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 id="titol-progres" class="text-lg font-semibold">Progrés del processament</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $progres['completades'] }} de {{ $progres['total'] }} fases confirmades</p>
            </div>
            <strong class="text-2xl tabular-nums text-emerald-700 dark:text-emerald-400">{{ $progres['percentatge'] }}%</strong>
        </div>

        <div class="mt-4 h-3 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700"
             role="progressbar" aria-label="Progrés del processament"
             aria-valuemin="0" aria-valuemax="100"
             aria-valuenow="{{ $progres['percentatge'] }}">
            <div class="h-full rounded-full bg-emerald-600 transition-all duration-300 dark:bg-emerald-400"
                 style="width: {{ $progres['percentatge'] }}%"></div>
        </div>

        <ol class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($progres['fases'] as $fase)
                <li wire:key="fase-progres-{{ $loop->index }}" class="flex items-center gap-2 text-sm">
                    <span aria-hidden="true" class="flex size-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold
                        {{ $fase['completada'] ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200' : 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400' }}">
                        {{ $fase['completada'] ? '✓' : $loop->iteration }}
                    </span>
                    <span class="{{ $fase['completada'] ? 'font-medium text-emerald-800 dark:text-emerald-200' : 'text-zinc-600 dark:text-zinc-400' }}">
                        {{ $fase['nom'] }}
                        <span class="sr-only">{{ $fase['completada'] ? 'completada' : 'pendent' }}</span>
                    </span>
                </li>
            @endforeach
        </ol>

        <p class="mt-4 text-xs text-zinc-500 dark:text-zinc-400">
            El progrés reflecteix les fases desades. Marca les confirmacions i prem «Desar processament» per actualitzar-lo.
        </p>
    </section>

    @if ($missatge !== '')
        <div role="status" class="rounded-lg border border-emerald-300 bg-emerald-50 p-3 text-sm text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">{{ $missatge }}</div>
    @endif

    @unless ($mostra->recepcio_confirmada)
        <div role="alert" class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-200">
            La recepció no està confirmada. Torna al registre de mostres, edita-la i marca «Recepció confirmada» abans de processar-la.
        </div>
    @endunless

    <form wire:submit="guardar" class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_330px]">
        <div class="space-y-5">
            <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="mb-4 text-lg font-semibold">1. Descripció macroscòpica</h2>
                <label for="macro" class="mb-1 block text-sm font-medium">Descripció de la mostra</label>
                <textarea id="macro" rows="4" wire:model="formulari.descripcio_macroscopica" class="w-full rounded-lg border border-zinc-300 bg-transparent px-3 py-2 dark:border-zinc-600" @disabled(! auth()->user()->can('process', $mostra))></textarea>
                @error('formulari.descripcio_macroscopica') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                <label class="mt-3 flex items-center gap-2 text-sm">
                    <input type="checkbox" wire:model="formulari.descripcio_confirmada" class="rounded border-zinc-300" @disabled(! auth()->user()->can('process', $mostra))>
                    Descripció macroscòpica confirmada
                </label>
            </section>

            @if ($mostra->tipus_mostra === 'citologia')
                <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                    <h2 class="mb-3 text-lg font-semibold">2. Procediments de citologia</h2>
                    <p class="mb-3 text-sm text-zinc-500">Pots seleccionar més d'un procediment.</p>
                    <div class="grid gap-2 sm:grid-cols-2">
                        @foreach (\App\Livewire\Mostres\Proces::PROCEDIMENTS_CITOLOGIA as $procediment)
                            <label class="flex items-start gap-2 text-sm" wire:key="procediment-{{ md5($procediment) }}">
                                <input type="checkbox" wire:model="formulari.procediments" value="{{ $procediment }}" class="mt-0.5 rounded border-zinc-300" @disabled(! auth()->user()->can('process', $mostra))>
                                <span>{{ $procediment }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('formulari.procediments') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
                    <label class="mt-4 flex items-center gap-2 text-sm">
                        <input type="checkbox" wire:model="formulari.procediment_confirmat" class="rounded border-zinc-300" @disabled(! auth()->user()->can('process', $mostra))> Procediment confirmat
                    </label>
                </section>
            @else
                <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                    <h2 class="mb-4 text-lg font-semibold">2. Estació de tallat macroscòpic</h2>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="blocs" class="mb-1 block text-sm font-medium">Nombre de blocs</label>
                            <input id="blocs" type="number" min="1" max="100000" wire:model="formulari.numero_blocs" class="w-full rounded-lg border border-zinc-300 bg-transparent px-3 py-2 dark:border-zinc-600" @disabled(! auth()->user()->can('process', $mostra))>
                            @error('formulari.numero_blocs') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="talls" class="mb-1 block text-sm font-medium">Talls inclosos per bloc</label>
                            <textarea id="talls" rows="2" wire:model="formulari.talls_per_bloc" placeholder="Bloc 1: 4; Bloc 2: 1" class="w-full rounded-lg border border-zinc-300 bg-transparent px-3 py-2 dark:border-zinc-600" @disabled(! auth()->user()->can('process', $mostra))></textarea>
                        </div>
                    </div>
                    <label class="mt-3 flex items-center gap-2 text-sm">
                        <input type="checkbox" wire:model="formulari.tallat_macroscopic_confirmat" class="rounded border-zinc-300" @disabled(! auth()->user()->can('process', $mostra))> Tallat macroscòpic confirmat
                    </label>
                </section>
            @endif

            <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="mb-3 text-lg font-semibold">{{ $mostra->tipus_mostra === 'citologia' ? '3. Tincions' : '3. Tincions sol·licitades' }}</h2>
                <p class="mb-3 text-sm text-zinc-500">Selecció múltiple segons l'esborrany del circuit.</p>
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach ($mostra->tipus_mostra === 'citologia' ? \App\Livewire\Mostres\Proces::TINCIONS_CITOLOGIA : \App\Livewire\Mostres\Proces::TINCIONS_BIOPSIA as $tincio)
                        <label class="flex items-start gap-2 text-sm" wire:key="tincio-{{ md5($tincio) }}">
                            <input type="checkbox" wire:model="formulari.tincions" value="{{ $tincio }}" class="mt-0.5 rounded border-zinc-300" @disabled(! auth()->user()->can('process', $mostra))>
                            <span>{{ $tincio }}</span>
                        </label>
                    @endforeach
                </div>
                @error('formulari.tincions') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
                @if ($mostra->tipus_mostra === 'biopsia')
                    <label for="infotincions" class="mb-1 mt-4 block text-sm font-medium">Informació addicional sobre tincions</label>
                    <textarea id="infotincions" rows="2" wire:model="formulari.informacio_tincions" placeholder="Ex.: Bloc 2: PAS" class="w-full rounded-lg border border-zinc-300 bg-transparent px-3 py-2 dark:border-zinc-600" @disabled(! auth()->user()->can('process', $mostra))></textarea>
                @endif
                <label class="mt-4 flex items-center gap-2 text-sm">
                    <input type="checkbox" wire:model="formulari.tincions_confirmades" class="rounded border-zinc-300" @disabled(! auth()->user()->can('process', $mostra))>
                    {{ $mostra->tipus_mostra === 'citologia' ? 'Tincions confirmades' : 'Sol·licitud de tincions confirmada' }}
                </label>
            </section>

            @if ($mostra->tipus_mostra === 'biopsia')
                <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                    <h2 class="mb-4 text-lg font-semibold">4. Processament, inclusió i microtomia</h2>
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ([
                            'processador_teixits_confirmat' => 'Processador de teixits',
                            'inclusio_parafina_confirmada' => 'Centre d’inclusió en parafina',
                            'confeccio_blocs_confirmada' => 'Confecció i tallat dels blocs',
                            'lliurament_talls_confirmat' => 'Lliurament dels talls',
                            'tincio_confirmada' => 'Tinció realitzada',
                        ] as $camp => $etiqueta)
                            <label class="flex items-start gap-2 text-sm" wire:key="etapa-{{ $camp }}">
                                <input type="checkbox" wire:model="formulari.{{ $camp }}" class="mt-0.5 rounded border-zinc-300" @disabled(! auth()->user()->can('process', $mostra))>
                                {{ $etiqueta }}
                            </label>
                        @endforeach
                    </div>
                    <label for="laminetes" class="mb-1 mt-5 block text-sm font-medium">Microtomia: laminetes per bloc</label>
                    <textarea id="laminetes" rows="2" wire:model="formulari.laminetes_per_bloc" placeholder="Bloc 1: 3; Bloc 2: 2" class="w-full rounded-lg border border-zinc-300 bg-transparent px-3 py-2 dark:border-zinc-600" @disabled(! auth()->user()->can('process', $mostra))></textarea>
                    <p class="mt-1 text-xs text-zinc-500">Registre provisional en text, pendent de definir la identificació individual dels blocs.</p>
                </section>
            @endif

            <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="mb-4 text-lg font-semibold">{{ $mostra->tipus_mostra === 'citologia' ? '4. Lliurament, diagnòstic i arxiu' : '5. Lliurament, diagnòstic i arxiu' }}</h2>
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="formulari.muntatge_confirmat" class="rounded border-zinc-300" @disabled(! auth()->user()->can('process', $mostra))> Muntatge confirmat</label>
                    @if ($mostra->tipus_mostra === 'citologia')
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="formulari.lliurament_confirmat" class="rounded border-zinc-300" @disabled(! auth()->user()->can('process', $mostra))> Lliurament confirmat</label>
                    @else
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="formulari.lliurament_patoleg_confirmat" class="rounded border-zinc-300" @disabled(! auth()->user()->can('process', $mostra))> Lliurament al patòleg confirmat</label>
                    @endif
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="formulari.diagnostic_confirmat" @disabled(! auth()->user()->can('diagnose', $mostra)) class="rounded border-zinc-300"> Diagnòstic confirmat</label>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="formulari.arxiu_confirmat" @disabled(! auth()->user()->can('archive', $mostra)) class="rounded border-zinc-300"> Arxiu confirmat</label>
                </div>
                @error('formulari.diagnostic_confirmat') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
                @error('formulari.arxiu_confirmat') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
            </section>

            <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="mb-3 text-lg font-semibold">Incidències i observacions</h2>
                <textarea rows="4" wire:model="formulari.incidencies" aria-label="Incidències i observacions" class="w-full rounded-lg border border-zinc-300 bg-transparent px-3 py-2 dark:border-zinc-600" @disabled(! auth()->user()->can('process', $mostra))></textarea>
                @error('formulari.incidencies') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            </section>

            <div class="flex items-center gap-3">
                @canany(['process', 'diagnose'], $mostra)
                <button type="submit" wire:loading.attr="disabled" wire:target="guardar" @disabled(! $mostra->recepcio_confirmada)
                    class="rounded-lg bg-zinc-900 px-5 py-2 text-sm font-semibold text-white hover:bg-zinc-700 disabled:cursor-not-allowed disabled:opacity-40 dark:bg-white dark:text-zinc-900">
                    Desar processament
                </button>
                @endcanany
                <span wire:loading wire:target="guardar" class="text-sm text-zinc-500">Desant…</span>
            </div>
        </div>

        <aside class="self-start rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="text-lg font-semibold">Historial de la mostra</h2>
            <p class="mt-1 text-xs text-zinc-500">Darrers 100 esdeveniments, del més recent al més antic.</p>
            <ol class="mt-5 space-y-4 border-l border-zinc-200 pl-4 dark:border-zinc-700">
                @forelse ($historial as $item)
                    <li wire:key="event-{{ $item->id }}" class="relative">
                        <span class="absolute -left-[21px] top-1.5 h-2.5 w-2.5 rounded-full bg-emerald-600 ring-2 ring-white dark:ring-zinc-900"></span>
                        <p class="text-sm font-medium">{{ str_replace('_', ' ', ucfirst($item->accio)) }}</p>
                        <p class="text-xs text-zinc-500">{{ $item->usuari_nom ?: 'Usuari no disponible' }} · {{ $item->created_at?->format('d/m/Y H:i:s') }}</p>
                        @if (! empty($item->detall['estat_nou']) && $item->detall['estat_anterior'] !== $item->detall['estat_nou'])
                            <p class="mt-1 text-xs text-zinc-600 dark:text-zinc-400">Estat: {{ $item->detall['estat_anterior'] ?? '—' }} → {{ $item->detall['estat_nou'] }}</p>
                        @endif
                        @if (! empty($item->detall['camps_modificats']))
                            <p class="mt-1 text-xs text-zinc-500">Camps: {{ implode(', ', $item->detall['camps_modificats']) }}</p>
                        @endif
                    </li>
                @empty
                    <li class="text-sm text-zinc-500">Encara no hi ha esdeveniments registrats.</li>
                @endforelse
            </ol>
        </aside>
    </form>
</div>
