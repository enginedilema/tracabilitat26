<?php

namespace App\Livewire\Etiquetes;

use App\Models\Mostra;
use App\Models\QrLot;
use App\Models\User;
use App\Services\QrPreimpressioService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class GeneradorEtiquetes extends Component
{
    public int $quantitat = 10;
    public string $cerca = '';
    public array $seleccionades = [];
    public array $copies = [];

    public function mount(): void
    {
        $this->seleccionades = [];
        $this->copies = [];
    }

    // Generació d'un lot de QR sense assignar a mostres.
    public function generar(QrPreimpressioService $servei): mixed
    {
        Gate::authorize('viewAny', User::class);

        $this->validate([
            'quantitat' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $lot = $servei->crearLot($this->quantitat);

        return redirect()->route('etiquetes.a4.lot', ['lot' => $lot->id]);
    }

    // Selecció de mostres per imprimir les seves etiquetes.
    public function toggleMostra(int $mostraId): void
    {
        if (in_array($mostraId, $this->seleccionades, true)) {
            $this->seleccionades = array_values(array_filter(
                $this->seleccionades,
                fn ($id) => $id !== $mostraId
            ));
        } else {
            $this->seleccionades[] = $mostraId;
        }

        if (! isset($this->copies[$mostraId])) {
            $this->copies[$mostraId] = 1;
        }
    }

    public function imprimir(): mixed
    {
        $items = collect($this->seleccionades)
            ->map(fn ($id) => [
                'mostra_id' => (int) $id,
                'copies' => max(1, (int) ($this->copies[$id] ?? 1)),
            ])
            ->values()
            ->all();

        if (empty($items)) {
            $this->addError('seleccionades', 'Selecciona com a mínim una mostra.');

            return null;
        }

        session(['etiquetes_a4.items' => $items]);

        return redirect()->route('etiquetes.mostres.a4');
    }

    public function getMostresProperty()
    {
        return Mostra::query()
            ->when($this->cerca !== '', function ($query) {
                $query->where(function ($q) {
                    $q->where('identificacio', 'like', '%' . $this->cerca . '%')
                        ->orWhere('procedencia', 'like', '%' . $this->cerca . '%')
                        ->orWhere('sollicitant', 'like', '%' . $this->cerca . '%');
                });
            })
            ->orderByDesc('data_entrada')
            ->limit(50)
            ->get();
    }

    public function render(): View
    {
        Gate::authorize('viewAny', User::class);

        return view('livewire.etiquetes.generador-etiquetes', [
            'lots' => QrLot::query()->withCount([
                'etiquetes as disponibles' => fn ($q) => $q->where('estat', 'disponible'),
                'etiquetes as assignades' => fn ($q) => $q->where('estat', 'assignat'),
                'etiquetes as anullades' => fn ($q) => $q->where('estat', 'anullat'),
            ])->latest()->limit(20)->get(),
            'mostres' => $this->getMostresProperty(),
        ]);
    }
}
