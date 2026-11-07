<x-layouts::app :title="'Lector de codis QR'">
    <div data-qr-scanner class="mx-auto flex w-full max-w-4xl flex-col gap-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-zinc-900 dark:text-white">Lector de codis QR</h1>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Localitza una mostra amb la càmera o un lector USB.</p>
        </div>

        <div class="grid gap-5 md:grid-cols-2">
            <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="mb-3 text-lg font-semibold">Llegir amb la càmera</h2>
                <div class="overflow-hidden rounded-lg bg-zinc-950">
                    <video data-qr-video autoplay muted playsinline
                        class="aspect-square w-full object-cover" aria-label="Previsualització de la càmera"></video>
                </div>
                <p data-qr-status class="mt-3 text-sm text-zinc-600 dark:text-zinc-400" role="status" aria-live="polite">
                    Prem «Activar càmera» per començar.
                </p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <button data-qr-start type="button" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white dark:bg-white dark:text-zinc-900">
                        Activar càmera
                    </button>
                    <button data-qr-stop type="button" disabled class="rounded-lg border border-zinc-300 px-4 py-2 text-sm disabled:opacity-50 dark:border-zinc-600">
                        Aturar càmera
                    </button>
                </div>
            </section>

            <section class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="mb-3 text-lg font-semibold">Lector USB o codi manual</h2>
                <p class="mb-4 text-sm text-zinc-600 dark:text-zinc-400">
                    Connecta el lector USB com a teclat. Escaneja el QR al camp següent i prem Retorn.
                </p>
                <form data-qr-form class="space-y-3">
                    <label for="qr-entry" class="block text-sm font-medium">Codi o adreça del QR</label>
                    <input id="qr-entry" data-qr-input type="text" autocomplete="off" spellcheck="false"
                        placeholder="Escaneja o enganxa el codi QR"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-zinc-900 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white" />
                    <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white dark:bg-white dark:text-zinc-900">
                        Obrir mostra
                    </button>
                </form>
                <p data-qr-error role="alert" class="mt-3 hidden text-sm text-rose-700 dark:text-rose-400"></p>
                <div class="mt-5 rounded-lg bg-zinc-50 p-3 text-xs text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                    El QR identifica la mostra, però no substitueix l'inici de sessió ni els permisos.
                </div>
            </section>
        </div>

        <a href="{{ route('mostres.index') }}" wire:navigate class="text-sm font-medium text-emerald-700 hover:underline dark:text-emerald-400">
            ← Tornar a mostres
        </a>
    </div>
</x-layouts::app>
