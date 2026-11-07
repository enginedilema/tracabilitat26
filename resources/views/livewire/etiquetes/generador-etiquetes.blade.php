<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold">Generador d’etiquetes</h1>
            <p class="text-zinc-600 dark:text-zinc-300">
                Selecciona les mostres i genera un full DINA4 amb codis QR.
            </p>
        </div>

        <flux:button variant="primary" wire:click="imprimir">
            Imprimir full DINA4
        </flux:button>
    </div>

    @error('seleccionades')
        <div class="rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-red-700">
            {{ $message }}
        </div>
    @enderror

    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <label class="mb-2 block text-sm font-medium">Cerca de mostres</label>
        <input
            type="text"
            wire:model.live.debounce.300ms="cerca"
            placeholder="Identificació, procedència o sol·licitant"
            class="w-full rounded-lg border border-zinc-300 px-4 py-2 dark:border-zinc-700 dark:bg-zinc-800"
        />
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
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
                    <tr>
                        <td class="px-4 py-3">
                            <input
                                type="checkbox"
                                wire:click="toggleMostra({{ $mostra->id }})"
                                @checked(in_array($mostra->id, $seleccionades, true))
                            >
                        </td>
                        <td class="px-4 py-3 font-medium">{{ $mostra->identificacio }}</td>
                        <td class="px-4 py-3">{{ ucfirst($mostra->tipus_mostra) }}</td>
                        <td class="px-4 py-3">
                            {{ optional($mostra->data_entrada)->format('d/m/Y') }}
                        </td>
                        <td class="px-4 py-3">
                            <input
                                type="number"
                                min="1"
                                wire:model="copies.{{ $mostra->id }}"
                                class="w-24 rounded-lg border border-zinc-300 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800"
                            >
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-zinc-500">
                            No s’han trobat mostres.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>