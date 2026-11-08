<div class="flex w-full flex-col gap-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('etiquetes.index') }}" class="text-sm text-emerald-700 hover:underline">← Tornar als lots</a>
            <h1 class="mt-2 text-2xl font-semibold">Gestió del lot #{{ $lot->id }}</h1>
            <p class="text-sm text-zinc-500">Creat el {{ $lot->created_at?->format('d/m/Y H:i') }}. La impressió només inclou els QR disponibles.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if ($disponibles > 0)
                <a href="{{ route('etiquetes.a4', $lot->id) }}" target="_blank" rel="noopener"
                   class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-zinc-900">
                    Imprimir disponibles ({{ $disponibles }})
                </a>
                <button type="button" wire:click="prepararLot"
                    class="rounded-lg border border-rose-300 px-4 py-2 text-sm text-rose-700 dark:border-rose-800 dark:text-rose-300">
                    Anul·lar els disponibles
                </button>
            @endif
        </div>
    </div>

    @if ($missatge !== '')
        <p role="status" class="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">{{ $missatge }}</p>
    @endif

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700"><p class="text-xs text-zinc-500">Total</p><p class="text-2xl font-semibold">{{ $lot->etiquetes->count() }}</p></div>
        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700"><p class="text-xs text-zinc-500">Disponibles</p><p class="text-2xl font-semibold">{{ $disponibles }}</p></div>
        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700"><p class="text-xs text-zinc-500">Assignades</p><p class="text-2xl font-semibold">{{ $assignades }}</p></div>
        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700"><p class="text-xs text-zinc-500">Anul·lades</p><p class="text-2xl font-semibold">{{ $anullades }}</p></div>
    </div>

    @if ($anullarTot || $etiquetaSeleccionada !== null)
        <form wire:submit="confirmarAnullacio" class="rounded-xl border border-rose-300 p-5 dark:border-rose-800">
            <h2 class="font-semibold text-rose-700 dark:text-rose-300">
                {{ $anullarTot ? 'Anul·lar tots els QR disponibles del lot' : 'Anul·lar una etiqueta QR' }}
            </h2>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">
                Aquesta acció és permanent. Un QR anul·lat no es podrà assignar. Els QR ja assignats es conserven.
            </p>
            <label for="motiu-anullacio" class="mt-4 block text-sm font-medium">Motiu de l'anul·lació (8–255 caràcters)</label>
            <textarea id="motiu-anullacio" wire:model="motiuAnullacio" rows="2" maxlength="255"
                class="mt-2 block w-full rounded-lg border border-zinc-300 bg-transparent p-3 dark:border-zinc-600"
                placeholder="Per exemple: full malmès, etiqueta perduda..."></textarea>
            @error('motiuAnullacio') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
            <label class="mt-4 flex items-start gap-2 text-sm">
                <input type="checkbox" wire:model="confirmat" class="mt-1">
                <span>Confirmo que els QR afectats no s'han d'utilitzar i que retiraré les etiquetes físiques que en tingui.</span>
            </label>
            @error('confirmat') <p class="mt-1 text-sm text-rose-600">Has de confirmar l'acció.</p> @enderror
            <div class="mt-4 flex flex-wrap gap-3">
                <button type="submit" wire:loading.attr="disabled" wire:target="confirmarAnullacio"
                    class="rounded-lg bg-rose-700 px-4 py-2 text-sm font-medium text-white disabled:opacity-60">Confirmar anul·lació</button>
                <button type="button" wire:click="cancelarAnullacio" class="rounded-lg border border-zinc-300 px-4 py-2 text-sm">Cancel·lar</button>
            </div>
        </form>
    @endif

    <section class="overflow-x-auto rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
        <h2 class="mb-3 text-lg font-semibold">Etiquetes del lot</h2>
        <table class="min-w-full text-sm">
            <thead><tr class="border-b text-left"><th class="p-2">Etiqueta</th><th class="p-2">Estat</th><th class="p-2">Mostra vinculada</th><th class="p-2">Anul·lació</th><th class="p-2">Acció</th></tr></thead>
            <tbody>
            @foreach ($lot->etiquetes as $etiqueta)
                <tr wire:key="etiqueta-{{ $etiqueta->id }}" class="border-b border-zinc-100 dark:border-zinc-800">
                    <td class="p-2 font-mono">{{ strtoupper(substr(str_replace('-', '', $etiqueta->token), -8)) }}</td>
                    <td class="p-2">{{ $etiqueta->substitucioOrigen ? 'Substituït' : match($etiqueta->estat) { 'disponible' => 'Disponible', 'assignat' => 'Assignat', 'anullat' => 'Anul·lat', default => $etiqueta->estat } }}</td>
                    <td class="p-2">{{ $etiqueta->mostra?->identificacio ?? $etiqueta->substitucioOrigen?->mostra?->identificacio ?? '—' }}</td>
                    <td class="p-2 text-xs">
                        @if ($etiqueta->estat === 'anullat')
                            {{ $etiqueta->motiu_anullacio ?? '—' }}
                            <span class="block text-zinc-500">{{ $etiqueta->anullat_at?->format('d/m/Y H:i') }} · {{ $etiqueta->anullatPer?->name ?? 'Usuari no disponible' }}</span>
                        @else
                            —
                        @endif
                    </td>
                    <td class="p-2">
                        @if ($etiqueta->estat === 'disponible' && $etiqueta->mostra_id === null)
                            <button type="button" wire:click="prepararEtiqueta({{ $etiqueta->id }})" class="text-rose-700 hover:underline dark:text-rose-300">Anul·lar</button>
                        @elseif ($etiqueta->estat === 'assignat' && $etiqueta->mostra_id !== null)
                            <a href="{{ route('mostres.qr.substituir', $etiqueta->mostra_id) }}"
                               class="text-emerald-700 hover:underline dark:text-emerald-300">Substituir QR</a>
                        @else
                            <span class="text-xs text-zinc-500">No disponible</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </section>
</div>
