<?php

namespace App\Livewire\Etiquetes;

use App\Models\QrLot;
use App\Models\User;
use App\Services\QrPreimpressioService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class GeneradorEtiquetes extends Component
{
    public int $quantitat = 10;

    public function generar(QrPreimpressioService $servei): mixed
    {
        Gate::authorize('viewAny', User::class); // Només administradors actius.

        $this->validate(['quantitat' => ['required', 'integer', 'min:1', 'max:100']]);
        $lot = $servei->crearLot($this->quantitat);

        return redirect()->route('etiquetes.a4', $lot);
    }

    public function render(): View
    {
        Gate::authorize('viewAny', User::class);

        return view('livewire.etiquetes.generador-etiquetes', [
            'lots' => QrLot::query()->withCount([
                'etiquetes as disponibles' => fn ($q) => $q->where('estat', 'disponible'),
                'etiquetes as assignades' => fn ($q) => $q->where('estat', 'assignat'),
            ])->latest()->limit(20)->get(),
        ]);
    }
}
