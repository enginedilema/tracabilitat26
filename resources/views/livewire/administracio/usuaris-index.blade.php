<div class="mx-auto flex w-full max-w-6xl flex-col gap-6">
    <header class="space-y-2">
        <h1 class="text-2xl font-semibold">Administració d'usuaris</h1>
        <p class="text-sm text-zinc-500">Accés exclusiu d'administrador. Els canvis de permisos queden registrats.</p>
    </header>

    @if ($missatge !== '')
        <div role="status" class="rounded-lg border border-emerald-300 bg-emerald-50 p-3 text-emerald-900">{{ $missatge }}</div>
    @endif
    @error('rols') <div role="alert" class="rounded-lg border border-rose-300 bg-rose-50 p-3 text-rose-900">{{ $message }}</div> @enderror

    <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
        <h2 class="mb-4 text-lg font-semibold">Crear usuari</h2>
        <form wire:submit="crear" class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <label class="text-sm">Nom complet
                <input type="text" wire:model="nom" autocomplete="off" class="mt-1 w-full rounded-lg border border-zinc-300 bg-transparent px-3 py-2 dark:border-zinc-600" />
                @error('nom') <span class="text-rose-600">{{ $message }}</span> @enderror
            </label>
            <label class="text-sm">Correu electrònic
                <input type="email" wire:model="email" autocomplete="off" class="mt-1 w-full rounded-lg border border-zinc-300 bg-transparent px-3 py-2 dark:border-zinc-600" />
                @error('email') <span class="text-rose-600">{{ $message }}</span> @enderror
            </label>
            <label class="text-sm">Contrasenya inicial (mínim 12 caràcters)
                <input type="password" wire:model="contrasenya" autocomplete="new-password" class="mt-1 w-full rounded-lg border border-zinc-300 bg-transparent px-3 py-2 dark:border-zinc-600" />
                @error('contrasenya') <span class="text-rose-600">{{ $message }}</span> @enderror
            </label>
            <label class="text-sm">Rol
                <select wire:model="rolNou" class="mt-1 w-full rounded-lg border border-zinc-300 bg-transparent px-3 py-2 dark:border-zinc-600">
                    <option value="tecnic">Tècnic</option><option value="patoleg">Patòleg</option><option value="admin">Administrador</option>
                </select>
                @error('rolNou') <span class="text-rose-600">{{ $message }}</span> @enderror
            </label>
            <div class="md:col-span-2">
                <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 font-semibold text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900">Crear usuari</button>
            </div>
        </form>
    </section>

    <section class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
        <h2 class="p-4 text-lg font-semibold">Comptes i permisos</h2>
        <table class="min-w-full divide-y divide-zinc-200 text-left text-sm dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-800"><tr><th class="p-3">Usuari</th><th class="p-3">Rol</th><th class="p-3">Accés actiu</th><th class="p-3">Acció</th></tr></thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                @foreach ($usuaris as $usuari)
                    <tr wire:key="usuari-{{ $usuari->id }}">
                        <td class="p-3"><strong>{{ $usuari->name }}</strong><div class="text-xs text-zinc-500">{{ $usuari->email }}</div></td>
                        <td class="p-3">
                            <select wire:model="rols.{{ $usuari->id }}" aria-label="Rol de {{ $usuari->name }}" class="rounded-lg border border-zinc-300 bg-transparent px-2 py-2 dark:border-zinc-600">
                                <option value="pendent">Pendent</option><option value="admin">Administrador</option><option value="tecnic">Tècnic</option><option value="patoleg">Patòleg</option>
                            </select>
                        </td>
                        <td class="p-3"><label class="inline-flex items-center gap-2"><input type="checkbox" wire:model="actius.{{ $usuari->id }}" /> <span>{{ $usuari->actiu ? 'Actiu' : 'Inactiu' }}</span></label></td>
                        <td class="p-3"><button type="button" wire:click="actualitzar({{ $usuari->id }})" class="rounded-lg border border-zinc-300 px-3 py-2 font-medium hover:bg-zinc-50 dark:border-zinc-600 dark:hover:bg-zinc-800">Desar</button></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>

    <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
        <h2 class="mb-3 text-lg font-semibold">Últims canvis de permisos</h2>
        <div class="space-y-3 text-sm">
            @forelse ($auditoria as $event)
                <div class="border-b border-zinc-100 pb-2 dark:border-zinc-700">
                    <strong>{{ $event->accio === 'creat' ? 'Compte creat' : 'Permisos modificats' }}</strong>
                    · {{ $event->usuari_email }}
                    <p class="text-xs text-zinc-500">Per {{ $event->actor_email ?: 'Desconegut' }} · {{ $event->created_at }}</p>
                </div>
            @empty
                <p class="text-zinc-500">Encara no s'han registrat canvis.</p>
            @endforelse
        </div>
    </section>
</div>
