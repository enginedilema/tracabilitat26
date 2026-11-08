<div class="flex w-full flex-col gap-6">
    <header>
        <a href="{{ route('mostres.proces', $mostra) }}" class="text-sm text-emerald-700 hover:underline">← Tornar al circuit</a>
        <h1 class="mt-2 text-2xl font-semibold">Substitució controlada del QR</h1>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">
            Mostra <strong>{{ $mostra->identificacio }}</strong> · {{ ucfirst($mostra->tipus_mostra) }}
        </p>
        <p class="mt-2 text-sm">QR actiu:
            @if ($activa)
                <span class="font-mono">{{ strtoupper(substr(str_replace('-', '', $activa->token), -8)) }}</span> (lot #{{ $activa->lot_id }})
            @elseif ($mostra->qr_token)
                Codi antic de la v0.6
            @else
                Cap codi assignat
            @endif
        </p>
    </header>

    @if ($missatge !== '')
        <p role="status" class="rounded-lg border border-emerald-300 bg-emerald-50 p-4 text-sm text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">{{ $missatge }}</p>
    @endif

    @if ($mostra->estat === 'arxivada')
        <p class="rounded-lg border border-amber-300 p-4 text-sm">La mostra està arxivada. No es permet substituir-ne el QR.</p>
    @elseif (! $activa && ! $mostra->qr_token)
        <p class="rounded-lg border border-amber-300 p-4 text-sm">La mostra no té cap QR assignat. No es pot executar una substitució.</p>
    @else
    <form wire:submit="confirmar" class="space-y-4 rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
        <div class="rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-200">
            El QR actual quedarà invalidat. El nou QR ha d'estar disponible en un lot preimprès. Cal retirar o identificar com a no vàlida l'etiqueta anterior.
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium" for="qrNou">Nou QR preimprès *</label>
            <input type="text" id="qrNou" wire:model="qrNou" autocomplete="off" spellcheck="false" required
                placeholder="Escaneja l'etiqueta nova o enganxa l'UUID"
                class="w-full rounded-lg border border-zinc-300 bg-transparent px-3 py-2 dark:border-zinc-600" />
            @error('qrNou') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium" for="motiu">Motiu de la substitució *</label>
            <textarea id="motiu" wire:model="motiu" rows="3" maxlength="255" required
                placeholder="Per exemple: l'etiqueta s'ha deteriorat durant la recepció"
                class="w-full rounded-lg border border-zinc-300 bg-transparent px-3 py-2 dark:border-zinc-600"></textarea>
            @error('motiu') <p class="mt-1 text-sm text-rose-700">{{ $message }}</p> @enderror
        </div>
        <label class="flex items-start gap-2 text-sm">
            <input type="checkbox" wire:model="confirmat" class="mt-1" />
            <span>Confirmo que he comprovat la identitat de la mostra, que el nou QR és correcte i que l'etiqueta anterior es retirarà o es marcarà com a no vàlida.</span>
        </label>
        @error('confirmat') <p class="text-sm text-rose-700">{{ $message }}</p> @enderror
        <button type="submit" wire:loading.attr="disabled" wire:target="confirmar"
            class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50 dark:bg-white dark:text-zinc-900">Confirmar la substitució</button>
    </form>
    @endif

    <section class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
        <h2 class="mb-3 text-lg font-semibold">Historial de substitucions</h2>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead><tr class="border-b"><th class="p-2">Data</th><th class="p-2">QR anterior</th><th class="p-2">QR nou</th><th class="p-2">Motiu</th><th class="p-2">Usuari</th></tr></thead>
                <tbody>
                    @forelse($historial as $canvi)
                    <tr class="border-b border-zinc-100 dark:border-zinc-800">
                        <td class="p-2">{{ $canvi->created_at?->format('d/m/Y H:i') }}</td>
                        <td class="p-2 font-mono">{{ $canvi->antiga ? strtoupper(substr(str_replace('-', '', $canvi->antiga->token), -8)) : 'QR v0.6' }}</td>
                        <td class="p-2 font-mono">{{ $canvi->nova ? strtoupper(substr(str_replace('-', '', $canvi->nova->token), -8)) : '—' }}</td>
                        <td class="p-2">{{ $canvi->motiu }}</td>
                        <td class="p-2">{{ $canvi->autor?->name ?? 'Usuari no disponible' }}</td>
                    </tr>
                    @empty
                    <tr><td class="p-3 text-zinc-500" colspan="5">Encara no hi ha substitucions.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
