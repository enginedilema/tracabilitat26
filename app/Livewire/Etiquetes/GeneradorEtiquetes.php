<?php

namespace App\Livewire\Etiquetes;

use App\Models\Mostra;
use Livewire\Component;

class GeneradorEtiquetes extends Component
{
    public string $cerca = '';
    public array $seleccionades = [];
    public array $copies = [];

    public function mount(): void
    {
        $this->seleccionades = [];
        $this->copies = [];
    }

    public function toggleMostra(int $mostraId): void
    {
        if (in_array($mostraId, $this->seleccionades, true)) {
            $this->seleccionades = array_values(
                array_filter(
                    $this->seleccionades,
                    fn ($id) => $id !== $mostraId
                )
            );
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
            ->map(function ($id) {
                return [
                    'mostra_id' => (int) $id,
                    'copies' => max(1, (int) ($this->copies[$id] ?? 1)),
                ];
            })
            ->values()
            ->all();

        if (empty($items)) {
            $this->addError('seleccionades', 'Selecciona com a mínim una mostra.');
            return null;
        }

        session(['etiquetes_a4.items' => $items]);

        return redirect()->route('etiquetes.a4');
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

    public function render()
    {
        return view('livewire.etiquetes.generador-etiquetes', [
            'mostres' => $this->mostres,
        ]);
    }
}