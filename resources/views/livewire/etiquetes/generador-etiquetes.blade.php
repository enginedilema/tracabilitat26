<div class="flex w-full flex-col gap-6">
    <div>
        <h1 class="text-2xl font-semibold">Etiquetes QR abans de registrar mostres</h1>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">Genera i imprimeix codis únics sense dades de cap mostra. Després escaneja una etiqueta en registrar la mostra.</p>
    </div>

    <form wire:submit="generar" class="flex flex-col gap-4 rounded-xl border border-zinc-200 bg-white p-5 sm:flex-row sm:items-end dark:border-zinc-700 dark:bg-zinc-900">
        <div>
            <label for="quantitat" class="block text-sm font-medium">Nombre d'etiquetes (1–100)</label>
            <input id="quantitat" type="number" min="1" max="100" step="1" wire:model="quantitat"
                class="mt-2 w-36 rounded-lg border border-zinc-300 bg-transparent px-3 py-2 dark:border-zinc-600" />
            @error('quantitat') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
        <button type="submit" wire:loading.attr="disabled" wire:target="generar"
            class="rounded-lg bg-zinc-900 px-4 py-2 font-medium text-white disabled:opacity-60 dark:bg-white dark:text-zinc-900">Generar fulls DINA4</button>
    </form>

    <section class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
        <h2 class="mb-3 text-lg font-semibold">Lots generats</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead><tr class="border-b text-left"><th class="p-2">Lot</th><th class="p-2">Data</th><th class="p-2">Total</th><th class="p-2">Disponibles</th><th class="p-2">Assignades</th><th class="p-2">Anul·lades</th><th class="p-2">Gestió</th></tr></thead>
                <tbody>
                    @forelse($lots as $lot)
                    <tr class="border-b border-zinc-100 dark:border-zinc-800">
                        <td class="p-2">#{{ $lot->id }}</td>
                        <td class="p-2">{{ $lot->created_at?->format('d/m/Y H:i') }}</td>
                        <td class="p-2">{{ $lot->quantitat }}</td>
                        <td class="p-2">{{ $lot->disponibles }}</td>
                        <td class="p-2">{{ $lot->assignades }}</td>
                        <td class="p-2">{{ $lot->anullades }}</td>
                        <td class="p-2">
                            <a class="font-semibold text-emerald-700 hover:underline" href="{{ route('etiquetes.lot', $lot) }}">Gestionar</a>
                            @if ($lot->disponibles > 0)
                                <span class="px-1 text-zinc-400">·</span>
                                <a class="font-semibold text-emerald-700 hover:underline" href="{{ route('etiquetes.a4', $lot) }}" target="_blank" rel="noopener">Imprimir disponibles</a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="p-4 text-center text-zinc-500">Encara no hi ha lots.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <p class="mt-3 text-xs text-zinc-500">Reimprimir un lot pot crear duplicats físics. Retira o destrueix els fulls anteriors abans de tornar a imprimir.</p>
    </section>
</div>
