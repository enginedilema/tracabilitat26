<div class="flex w-full flex-col gap-8">
    <header>
        <h1 class="text-3xl font-bold">Generador d'etiquetes</h1>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">
            Genera codis QR abans de registrar mostres o imprimeix etiquetes de mostres existents.
        </p>
    </header>

    {{-- Opció 1: codis QR sense mostra assignada --}}
    <section class="space-y-5">
        <div>
            <h2 class="text-xl font-semibold">Etiquetes QR abans de registrar mostres</h2>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">
                Genera i imprimeix codis únics sense dades de cap mostra.
                Després escaneja una etiqueta en registrar la mostra.
            </p>
        </div>

        <form wire:submit="generar"
              class="flex flex-col gap-4 rounded-xl border border-zinc-200 bg-white p-5 sm:flex-row sm:items-end dark:border-zinc-700 dark:bg-zinc-900">
            <div>
                <label for="quantitat" class="block text-sm font-medium">Nombre d'etiquetes (1–100)</label>
                <input id="quantitat" type="number" min="1" max="100" step="1" wire:model="quantitat"
                       class="mt-2 w-36 rounded-lg border border-zinc-300 bg-transparent px-3 py-2 dark:border-zinc-600" />
                @error('quantitat')
                    <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" wire:loading.attr="disabled" wire:target="generar"
                    class="rounded-lg bg-zinc-900 px-4 py-2 font-medium text-white disabled:opacity-60 dark:bg-white dark:text-zinc-900">
                Generar fulls DINA4
            </button>
        </form>

        <div class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
            <h3 class="mb-3 text-lg font-semibold">Lots generats</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b text-left">
                            <th class="p-2">Lot</th>
                            <th class="p-2">Data</th>
                            <th class="p-2">Total</th>
                            <th class="p-2">Disponibles</th>
                            <th class="p-2">Assignades</th>
                            <th class="p-2">Anul·lades</th>
                            <th class="p-2">Gestió</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($lots as $lot)
                            <tr class="border-b border-zinc-100 dark:border-zinc-800" wire:key="lot-{{ $lot->id }}">
                                <td class="p-2">#{{ $lot->id }}</td>
                                <td class="p-2">{{ $lot->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="p-2">{{ $lot->quantitat }}</td>
                                <td class="p-2">{{ $lot->disponibles }}</td>
                                <td class="p-2">{{ $lot->assignades }}</td>
                                <td class="p-2">{{ $lot->anullades }}</td>
                                <td class="p-2">
                                    <a class="font-semibold text-emerald-700 hover:underline"
                                       href="{{ route('etiquetes.lot', $lot) }}">Gestionar</a>
                                    @if ($lot->disponibles > 0)
                                        <span class="px-1 text-zinc-400">·</span>
                                        <a class="font-semibold text-emerald-700 hover:underline"
                                           href="{{ route('etiquetes.a4.lot', ['lot' => $lot->id]) }}"
                                           target="_blank" rel="noopener">Imprimir disponibles</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="p-4 text-center text-zinc-500">Encara no hi ha lots.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="mt-3 text-xs text-zinc-500">
                Reimprimir un lot pot crear duplicats físics. Retira o destrueix els fulls anteriors abans de tornar a imprimir.
            </p>
        </div>
    </section>

    {{-- Opció 2: etiquetes de mostres ja registrades --}}
    <section class="space-y-5 border-t border-zinc-200 pt-7 dark:border-zinc-700">
        <div>
            <h2 class="text-xl font-semibold">Etiquetes de mostres registrades</h2>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">
                Selecciona les mostres i genera un full DINA4 amb codis QR.
            </p>
        </div>

        @error('seleccionades')
            <div class="rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-red-700">
                {{ $message }}
            </div>
        @enderror

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <label for="cerca-mostres" class="mb-2 block text-sm font-medium">Cerca de mostres</label>
            <input id="cerca-mostres" type="text" wire:model.live.debounce.300ms="cerca"
                   placeholder="Identificació, procedència o sol·licitant"
                   class="w-full rounded-lg border border-zinc-300 px-4 py-2 dark:border-zinc-700 dark:bg-zinc-800" />
        </div>

        <form wire:submit="imprimir" class="space-y-4">
            <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th class="px-4 py-3 text-left">Sel.</th>
                            <th class="px-4 py-3 text-left">Identificació</th>
                            <th class="px-4 py-3 text-left">Tipus</th>
                            <th class="px-4 py-3 text-left">Entrada</th>
                            <th class="px-4 py-3 text-left">Còpies</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse ($mostres as $mostra)
                            <tr wire:key="mostra-{{ $mostra->id }}">
                                <td class="px-4 py-3">
                                    <input type="checkbox" wire:click="toggleMostra({{ $mostra->id }})"
                                           @checked(in_array($mostra->id, $seleccionades, true))
                                           aria-label="Seleccionar mostra {{ $mostra->identificacio }}" />
                                </td>
                                <td class="px-4 py-3 font-medium">{{ $mostra->identificacio }}</td>
                                <td class="px-4 py-3">{{ ucfirst($mostra->tipus_mostra) }}</td>
                                <td class="px-4 py-3">{{ optional($mostra->data_entrada)->format('d/m/Y') }}</td>
                                <td class="px-4 py-3">
                                    <input type="number" min="1" step="1"
                                           wire:model="copies.{{ $mostra->id }}"
                                           aria-label="Còpies de la mostra {{ $mostra->identificacio }}"
                                           class="w-24 rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800" />
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-zinc-500">No s'han trobat mostres.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <button type="submit" wire:loading.attr="disabled" wire:target="imprimir"
                    class="rounded-lg bg-zinc-900 px-4 py-2 font-medium text-white disabled:opacity-60 dark:bg-white dark:text-zinc-900">
                Imprimir full DINA4 de les mostres seleccionades
            </button>
        </form>
    </section>
</div>
