<?php

namespace App\Livewire\Mostres;

use App\Models\Mostra;
use App\Models\QrEtiqueta;
use App\Models\QrSubstitucio;
use App\Models\User;
use App\Services\QrSubstitucioService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class SubstituirQr extends Component
{
    public int $mostraId;
    public string $qrNou = '';
    public string $motiu = '';
    public bool $confirmat = false;
    public string $missatge = '';

    public function mount(int $mostraId): void
    {
        Gate::authorize('viewAny', User::class);
        Mostra::query()->findOrFail($mostraId);
        $this->mostraId = $mostraId;
    }

    public function confirmar(QrSubstitucioService $servei): void
    {
        Gate::authorize('viewAny', User::class);
        $this->validate([
            'qrNou' => ['required', 'string', 'max:512'],
            'motiu' => ['required', 'string', 'min:8', 'max:255'],
            'confirmat' => ['accepted'],
        ]);

        $mostra = Mostra::query()->findOrFail($this->mostraId);
        $servei->substituir($mostra, $this->qrNou, $this->motiu);

        $this->reset('qrNou', 'motiu', 'confirmat');
        $this->resetValidation();
        $this->missatge = 'QR substituït. El codi anterior ha quedat invalidat. Retira l’etiqueta antiga si la conserves.';
    }

    public function render(): View
    {
        Gate::authorize('viewAny', User::class);
        $mostra = Mostra::query()->findOrFail($this->mostraId);
        $activa = QrEtiqueta::query()->where('mostra_id', $mostra->id)->where('estat', 'assignat')->first();
        $historial = QrSubstitucio::query()
            ->with(['antiga', 'nova', 'autor'])
            ->where('mostra_id', $mostra->id)
            ->latest('id')->limit(25)->get();

        return view('livewire.mostres.substituir-qr', compact('mostra', 'activa', 'historial'));
    }
}
