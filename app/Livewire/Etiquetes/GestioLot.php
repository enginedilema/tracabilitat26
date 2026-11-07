<?php

namespace App\Livewire\Etiquetes;

use App\Models\QrEtiqueta;
use App\Models\QrLot;
use App\Models\User;
use App\Services\QrPreimpressioService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class GestioLot extends Component
{
    public int $lotId;
    public ?int $etiquetaSeleccionada = null;
    public bool $anullarTot = false;
    public bool $confirmat = false;
    public string $motiuAnullacio = '';
    public string $missatge = '';

    public function mount(int $lotId): void
    {
        Gate::authorize('viewAny', User::class);
        QrLot::query()->findOrFail($lotId);
        $this->lotId = $lotId;
    }

    public function prepararEtiqueta(int $etiquetaId): void
    {
        Gate::authorize('viewAny', User::class);
        $etiqueta = QrEtiqueta::query()->where('lot_id', $this->lotId)->findOrFail($etiquetaId);

        if ($etiqueta->estat !== 'disponible' || $etiqueta->mostra_id !== null) {
            throw ValidationException::withMessages([
                'motiuAnullacio' => 'Aquesta etiqueta ja no està disponible.',
            ]);
        }

        $this->reset('anullarTot', 'confirmat', 'motiuAnullacio', 'missatge');
        $this->etiquetaSeleccionada = $etiquetaId;
    }

    public function prepararLot(): void
    {
        Gate::authorize('viewAny', User::class);
        QrLot::query()->findOrFail($this->lotId);

        $this->reset('etiquetaSeleccionada', 'confirmat', 'motiuAnullacio', 'missatge');
        $this->anullarTot = true;
    }

    public function cancelarAnullacio(): void
    {
        Gate::authorize('viewAny', User::class);
        $this->reset('etiquetaSeleccionada', 'anullarTot', 'confirmat', 'motiuAnullacio');
        $this->resetErrorBag();
    }

    public function confirmarAnullacio(QrPreimpressioService $servei): void
    {
        Gate::authorize('viewAny', User::class);

        $this->validate([
            'motiuAnullacio' => ['required', 'string', 'min:8', 'max:255'],
            'confirmat' => ['accepted'],
        ]);

        if ($this->anullarTot) {
            $quantitat = $servei->anullarDisponiblesLot($this->lotId, $this->motiuAnullacio);
            $missatge = "S'han anul·lat {$quantitat} etiquetes disponibles del lot.";
        } elseif ($this->etiquetaSeleccionada !== null) {
            $servei->anullarEtiqueta($this->lotId, $this->etiquetaSeleccionada, $this->motiuAnullacio);
            $missatge = "S'ha anul·lat l'etiqueta seleccionada.";
        } else {
            throw ValidationException::withMessages([
                'motiuAnullacio' => 'Selecciona una etiqueta o el lot abans de continuar.',
            ]);
        }

        $this->reset('etiquetaSeleccionada', 'anullarTot', 'confirmat', 'motiuAnullacio');
        $this->missatge = $missatge;
    }

    public function render(): View
    {
        Gate::authorize('viewAny', User::class);

        $lot = QrLot::query()->with([
            'etiquetes' => fn ($query) => $query->with(['mostra', 'anullatPer'])->orderBy('id'),
        ])->findOrFail($this->lotId);

        return view('livewire.etiquetes.gestio-lot', [
            'lot' => $lot,
            'disponibles' => $lot->etiquetes->where('estat', 'disponible')->count(),
            'assignades' => $lot->etiquetes->where('estat', 'assignat')->count(),
            'anullades' => $lot->etiquetes->where('estat', 'anullat')->count(),
        ]);
    }
}
