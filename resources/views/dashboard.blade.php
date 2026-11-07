<x-layouts::app :title="__('Dashboard')">
    @can('viewAny', \App\Models\User::class)
        <livewire:dashboard.index />
    @else
        <div class="mx-auto max-w-3xl space-y-5 p-4">
            <h1 class="text-2xl font-semibold">Espai de treball</h1>
            <p class="text-zinc-600 dark:text-zinc-400">El dashboard d'administració és exclusiu per a administradors.</p>
            @can('viewAny', \App\Models\Mostra::class)
                <a href="{{ route('mostres.index') }}" wire:navigate class="inline-flex rounded-lg bg-zinc-900 px-4 py-2 font-semibold text-white dark:bg-white dark:text-zinc-900">Obrir mostres</a>
            @else
                <p role="alert" class="rounded-lg border border-amber-300 p-4">El teu compte està pendent d'autorització. Contacta amb l'administrador.</p>
            @endcan
        </div>
    @endcan
</x-layouts::app>
