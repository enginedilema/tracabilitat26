<div class="flex w-full flex-col gap-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-zinc-900 dark:text-white">Gestió de mostres</h1>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Registre de citologies i biòpsies</p>
        </div>
        @can('create', \App\Models\Mostra::class)
        <button type="button" wire:click="crear"
            class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900 dark:hover:bg-zinc-200">
            + Nova mostra
        </button>
        @endcan
    </div>

    @if ($missatge !== '')
        <div role="status" class="rounded-lg border border-emerald-300 bg-emerald-50 p-3 text-sm text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">
            {{ $missatge }}
        </div>
    @endif

    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <label for="cerca" class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300">Cerca de mostres</label>
        <input id="cerca" type="search" wire:model.live.debounce.350ms="cerca"
            placeholder="Identificació, procedència o sol·licitant"
            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-600 focus:outline-none dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
    </div>

    <div class="grid gap-3 rounded-xl border border-zinc-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-4 dark:border-zinc-700 dark:bg-zinc-900">
        <label class="block text-sm">Tipus
            <select wire:model.live="filtreTipus" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-600 dark:bg-zinc-800">
                <option value="">Tots</option><option value="citologia">Citologia</option><option value="biopsia">Biòpsia</option>
            </select>
        </label>
        <label class="block text-sm">Estat
            <select wire:model.live="filtreEstat" class="mt-1 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-600 dark:bg-zinc-800">
                <option value="">Tots</option><option value="pendent">Pendent</option><option value="rebuda">Rebuda</option><option value="en_proces">En procés</option><option value="finalitzada">Finalitzada</option><option value="arxivada">Arxivada</option>
            </select>
        </label>
        <label class="block text-sm">Entrada des de
            <input type="date" wire:model.live="filtreDesDe" class="mt-1 w-full rounded-lg border border-zinc-300 bg-transparent px-3 py-2 dark:border-zinc-600">
        </label>
        <label class="block text-sm">Entrada fins a
            <input type="date" wire:model.live="filtreFins" class="mt-1 w-full rounded-lg border border-zinc-300 bg-transparent px-3 py-2 dark:border-zinc-600">
        </label>
    </div>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="min-w-full divide-y divide-zinc-200 text-left text-sm dark:divide-zinc-700">
            <thead class="bg-zinc-50 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                <tr>
                    <th class="px-4 py-3 font-semibold">Identificació</th>
                    <th class="px-4 py-3 font-semibold">Tipus</th>
                    <th class="px-4 py-3 font-semibold">Entrada</th>
                    <th class="px-4 py-3 font-semibold">Recepció</th>
                    <th class="px-4 py-3 font-semibold">Estat</th>
                    <th class="px-4 py-3 text-right font-semibold">Accions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 bg-white text-zinc-800 dark:divide-zinc-800 dark:bg-zinc-900 dark:text-zinc-200">
                @forelse ($mostres as $mostra)
                    <tr wire:key="mostra-{{ $mostra->id }}">
                        <td class="whitespace-nowrap px-4 py-3 font-medium">{{ $mostra->identificacio }}</td>
                        <td class="whitespace-nowrap px-4 py-3">{{ $mostra->tipus_mostra === 'citologia' ? 'Citologia' : 'Biòpsia' }}</td>
                        <td class="whitespace-nowrap px-4 py-3">{{ $mostra->data_entrada?->format('d/m/Y') }}</td>
                        <td class="whitespace-nowrap px-4 py-3">
                            @if ($mostra->recepcio_confirmada)
                                <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">Confirmada</span>
                            @else
                                <span class="rounded-full bg-amber-100 px-2 py-1 text-xs text-amber-800 dark:bg-amber-950 dark:text-amber-200">Pendent</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <span class="rounded-full bg-zinc-100 px-2 py-1 text-xs font-semibold text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                                {{ match ($mostra->estat) {
                                    'pendent' => 'Pendent', 'rebuda' => 'Rebuda',
                                    'en_proces' => 'En procés', 'finalitzada' => 'Finalitzada',
                                    'arxivada' => 'Arxivada', default => $mostra->estat,
                                } }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            <div class="flex justify-end gap-3">
                                <a href="{{ route('mostres.qr.etiqueta', $mostra) }}" target="_blank" rel="noopener noreferrer" class="text-sm font-medium text-violet-700 hover:underline dark:text-violet-400">Etiqueta QR</a>
                                <a href="{{ route('mostres.proces', $mostra) }}" wire:navigate class="text-sm font-semibold text-emerald-700 hover:underline dark:text-emerald-400">Circuit</a>
                                <button type="button" wire:click="consultar({{ $mostra->id }})" class="text-sm font-medium text-sky-700 hover:underline dark:text-sky-400">Veure</button>
                                @can('update', $mostra)
                                <button type="button" wire:click="editar({{ $mostra->id }})" class="text-sm font-medium text-zinc-800 hover:underline dark:text-zinc-200">Editar</button>
                                @endcan
                                @can('delete', $mostra)
                                <button type="button" wire:click="eliminar({{ $mostra->id }})" wire:confirm="Vols donar de baixa aquesta mostra?" class="text-sm font-medium text-rose-700 hover:underline dark:text-rose-400">Baixa</button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-zinc-500">No hi ha mostres que coincideixin amb la cerca.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $mostres->links() }}</div>

    @if ($consulta)
        <section class="rounded-xl border border-sky-200 bg-sky-50 p-5 dark:border-sky-800 dark:bg-sky-950/30">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">Detall de la mostra: {{ $consulta->identificacio }}</h2>
                <button type="button" wire:click="tancar" class="text-sm underline">Tancar</button>
            </div>
            <dl class="grid grid-cols-1 gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
                <div><dt class="font-medium">Tipus de mostra</dt><dd>{{ ucfirst($consulta->tipus_mostra) }}</dd></div>
                <div><dt class="font-medium">Entrada</dt><dd>{{ $consulta->data_entrada?->format('d/m/Y') }} · {{ substr((string) $consulta->hora_entrada, 0, 5) }}</dd></div>
                <div><dt class="font-medium">Gènere</dt><dd>{{ $consulta->genere ?: '—' }}</dd></div>
                <div><dt class="font-medium">Edat</dt><dd>{{ $consulta->edat ?? '—' }}</dd></div>
                <div><dt class="font-medium">Procedència</dt><dd>{{ $consulta->procedencia ?: '—' }}</dd></div>
                <div><dt class="font-medium">Sol·licitant</dt><dd>{{ $consulta->sollicitant ?: '—' }}</dd></div>
                <div class="sm:col-span-2"><dt class="font-medium">Motiu de la sol·licitud</dt><dd class="whitespace-pre-wrap">{{ $consulta->motiu_sollicitud ?: '—' }}</dd></div>
                @if ($consulta->tipus_mostra === 'citologia')
                    <div><dt class="font-medium">Tipus principal</dt><dd>{{ $consulta->tipus_principal ?: '—' }}</dd></div>
                    <div><dt class="font-medium">Tipus secundari</dt><dd>{{ $consulta->tipus_secundari ?: '—' }}</dd></div>
                @else
                    <div><dt class="font-medium">Òrgan/localització</dt><dd>{{ $consulta->organ_localitzacio ?: '—' }}</dd></div>
                @endif
                <div><dt class="font-medium">Recepció</dt><dd>{{ $consulta->recepcio_confirmada ? 'Confirmada' : 'Pendent' }}</dd></div>
                <div><dt class="font-medium">Circuit</dt><dd><a href="{{ route('mostres.proces', $consulta) }}" wire:navigate class="font-semibold text-emerald-700 hover:underline">Obrir processament i historial</a></dd></div>
                <div><dt class="font-medium">Etiqueta QR</dt><dd><a href="{{ route('mostres.qr.etiqueta', $consulta) }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-violet-700 hover:underline">Imprimir etiqueta de la mostra</a></dd></div>
            </dl>
        </section>
    @endif

    @if ($formulariObert)
        <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-5 flex items-center justify-between gap-3">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $editantId ? 'Editar mostra' : 'Nova mostra' }}</h2>
                <button type="button" wire:click="tancar" class="text-sm text-zinc-600 underline dark:text-zinc-300">Cancel·lar</button>
            </div>

            <form wire:submit="guardar" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label for="identificacio" class="mb-1 block text-sm font-medium">Identificació *</label>
                    <input id="identificacio" type="text" wire:model="identificacio" maxlength="255" required class="w-full rounded-lg border border-zinc-300 bg-transparent px-3 py-2 dark:border-zinc-600" />
                    @error('identificacio') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="tipus_mostra" class="mb-1 block text-sm font-medium">Tipus de mostra *</label>
                    <select id="tipus_mostra" wire:model.live="tipus_mostra" required class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-600 dark:bg-zinc-800">
                        <option value="citologia">Citologia</option>
                        <option value="biopsia">Biòpsia</option>
                    </select>
                    @error('tipus_mostra') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="data_entrada" class="mb-1 block text-sm font-medium">Data d'entrada *</label>
                    <input id="data_entrada" type="date" wire:model="data_entrada" required class="w-full rounded-lg border border-zinc-300 bg-transparent px-3 py-2 dark:border-zinc-600" />
                    @error('data_entrada') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="hora_entrada" class="mb-1 block text-sm font-medium">Hora d'entrada *</label>
                    <input id="hora_entrada" type="time" wire:model="hora_entrada" required class="w-full rounded-lg border border-zinc-300 bg-transparent px-3 py-2 dark:border-zinc-600" />
                    @error('hora_entrada') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="genere" class="mb-1 block text-sm font-medium">Gènere</label>
                    <select id="genere" wire:model="genere" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-600 dark:bg-zinc-800">
                        <option value="">No consta</option>
                        <option value="Dona">Dona</option>
                        <option value="Home">Home</option>
                        <option value="Altres">Altres</option>
                    </select>
                    @error('genere') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="edat" class="mb-1 block text-sm font-medium">Edat</label>
                    <input id="edat" type="number" min="0" max="120" wire:model="edat" class="w-full rounded-lg border border-zinc-300 bg-transparent px-3 py-2 dark:border-zinc-600" />
                    @error('edat') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="procedencia" class="mb-1 block text-sm font-medium">Procedència</label>
                    <input id="procedencia" type="text" wire:model="procedencia" maxlength="255" class="w-full rounded-lg border border-zinc-300 bg-transparent px-3 py-2 dark:border-zinc-600" />
                    @error('procedencia') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="sollicitant" class="mb-1 block text-sm font-medium">Sol·licitant</label>
                    <input id="sollicitant" type="text" wire:model="sollicitant" maxlength="255" class="w-full rounded-lg border border-zinc-300 bg-transparent px-3 py-2 dark:border-zinc-600" />
                    @error('sollicitant') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="md:col-span-2">
                    <label for="motiu_sollicitud" class="mb-1 block text-sm font-medium">Motiu de la sol·licitud</label>
                    <textarea id="motiu_sollicitud" wire:model="motiu_sollicitud" rows="3" class="w-full rounded-lg border border-zinc-300 bg-transparent px-3 py-2 dark:border-zinc-600"></textarea>
                    @error('motiu_sollicitud') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                @if ($tipus_mostra === 'citologia')
                    <div>
                        <label for="tipus_principal" class="mb-1 block text-sm font-medium">Tipus principal de citologia</label>
                        <input id="tipus_principal" type="text" wire:model="tipus_principal" maxlength="255" class="w-full rounded-lg border border-zinc-300 bg-transparent px-3 py-2 dark:border-zinc-600" />
                        @error('tipus_principal') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="tipus_secundari" class="mb-1 block text-sm font-medium">Tipus secundari de citologia</label>
                        <input id="tipus_secundari" type="text" wire:model="tipus_secundari" maxlength="255" class="w-full rounded-lg border border-zinc-300 bg-transparent px-3 py-2 dark:border-zinc-600" />
                        @error('tipus_secundari') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                @else
                    <div class="md:col-span-2">
                        <label for="organ_localitzacio" class="mb-1 block text-sm font-medium">Òrgan o localització de la biòpsia</label>
                        <input id="organ_localitzacio" type="text" wire:model="organ_localitzacio" maxlength="255" class="w-full rounded-lg border border-zinc-300 bg-transparent px-3 py-2 dark:border-zinc-600" />
                        @error('organ_localitzacio') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                @endif
                <label class="flex items-center gap-2 text-sm md:col-span-2">
                    <input type="checkbox" wire:model="recepcio_confirmada" class="rounded border-zinc-300" />
                    Recepció confirmada
                </label>
                <div class="flex gap-3 md:col-span-2">
                    <button type="submit" wire:loading.attr="disabled" wire:target="guardar"
                        class="rounded-lg bg-zinc-900 px-5 py-2 text-sm font-semibold text-white hover:bg-zinc-700 disabled:opacity-50 dark:bg-white dark:text-zinc-900">
                        Guardar mostra
                    </button>
                    <button type="button" wire:click="tancar" class="rounded-lg border border-zinc-300 px-5 py-2 text-sm dark:border-zinc-600">Cancel·lar</button>
                </div>
            </form>
        </section>
    @endif
</div>
