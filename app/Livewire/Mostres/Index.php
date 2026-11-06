<?php

namespace App\Livewire\Mostres;

use App\Models\Mostra;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $cerca = '';
    public bool $formulariObert = false;
    public ?int $editantId = null;
    public ?int $consultantId = null;
    public string $missatge = '';

    public string $identificacio = '';
    public string $tipus_mostra = 'citologia';
    public string $genere = '';
    public ?string $edat = null;
    public string $motiu_sollicitud = '';
    public string $procedencia = '';
    public string $sollicitant = '';
    public string $data_entrada = '';
    public string $hora_entrada = '';
    public string $tipus_principal = '';
    public string $tipus_secundari = '';
    public string $organ_localitzacio = '';
    public bool $recepcio_confirmada = false;

    public function updatedCerca(): void
    {
        $this->resetPage();
    }

    public function crear(): void
    {
        $this->reiniciarFormulari();
        $this->formulariObert = true;
        $this->consultantId = null;
    }

    public function consultar(int $id): void
    {
        $this->consultantId = Mostra::query()->findOrFail($id)->id;
        $this->formulariObert = false;
        $this->missatge = '';
    }

    public function editar(int $id): void
    {
        $mostra = Mostra::query()->findOrFail($id);
        $this->reiniciarFormulari();

        $this->editantId = $mostra->id;
        $this->identificacio = $mostra->identificacio;
        $this->tipus_mostra = $mostra->tipus_mostra;
        $this->genere = $mostra->genere ?? '';
        $this->edat = $mostra->edat === null ? '' : (string) $mostra->edat;
        $this->motiu_sollicitud = $mostra->motiu_sollicitud ?? '';
        $this->procedencia = $mostra->procedencia ?? '';
        $this->sollicitant = $mostra->sollicitant ?? '';
        $this->data_entrada = $mostra->data_entrada?->format('Y-m-d') ?? '';
        $this->hora_entrada = substr((string) $mostra->hora_entrada, 0, 5);
        $this->tipus_principal = $mostra->tipus_principal ?? '';
        $this->tipus_secundari = $mostra->tipus_secundari ?? '';
        $this->organ_localitzacio = $mostra->organ_localitzacio ?? '';
        $this->recepcio_confirmada = (bool) $mostra->recepcio_confirmada;

        $this->consultantId = null;
        $this->formulariObert = true;
    }

    public function guardar(): void
    {
        abort_unless(auth()->check(), 403);

        if ($this->edat === '') {
            $this->edat = null;
        }

        $dades = $this->validate([
            'identificacio' => ['required', 'string', 'max:255', Rule::unique('mostres', 'identificacio')->ignore($this->editantId)],
            'tipus_mostra' => ['required', Rule::in(['citologia', 'biopsia'])],
            'genere' => ['nullable', 'string', 'max:20'],
            'edat' => ['nullable', 'integer', 'between:0,120'],
            'motiu_sollicitud' => ['nullable', 'string', 'max:10000'],
            'procedencia' => ['nullable', 'string', 'max:255'],
            'sollicitant' => ['nullable', 'string', 'max:255'],
            'data_entrada' => ['required', 'date_format:Y-m-d'],
            'hora_entrada' => ['required', 'date_format:H:i'],
            'tipus_principal' => ['nullable', 'string', 'max:255'],
            'tipus_secundari' => ['nullable', 'string', 'max:255'],
            'organ_localitzacio' => ['nullable', 'string', 'max:255'],
            'recepcio_confirmada' => ['boolean'],
        ]);

        foreach (['genere', 'edat', 'motiu_sollicitud', 'procedencia', 'sollicitant', 'tipus_principal', 'tipus_secundari', 'organ_localitzacio'] as $camp) {
            if ($dades[$camp] === '') {
                $dades[$camp] = null;
            }
        }

        if ($dades['tipus_mostra'] === 'citologia') {
            $dades['organ_localitzacio'] = null;
        } else {
            $dades['tipus_principal'] = null;
            $dades['tipus_secundari'] = null;
        }

        if ($this->editantId !== null) {
            Mostra::query()->findOrFail($this->editantId)->update($dades);
            $missatge = 'Mostra actualitzada correctament.';
        } else {
            Mostra::query()->create($dades);
            $missatge = 'Mostra registrada correctament.';
        }

        $this->reiniciarFormulari();
        $this->resetPage();
        $this->missatge = $missatge;
    }

    public function eliminar(int $id): void
    {
        abort_unless(auth()->check(), 403);

        Mostra::query()->findOrFail($id)->delete();

        if ($this->consultantId === $id) {
            $this->consultantId = null;
        }

        if ($this->editantId === $id) {
            $this->reiniciarFormulari();
        }

        $this->missatge = 'Mostra donada de baixa. El registre es conserva a la base de dades.';
    }

    public function tancar(): void
    {
        $this->reiniciarFormulari();
        $this->consultantId = null;
    }

    private function reiniciarFormulari(): void
    {
        $this->reset([
            'identificacio', 'tipus_mostra', 'genere', 'edat', 'motiu_sollicitud',
            'procedencia', 'sollicitant', 'data_entrada', 'hora_entrada',
            'tipus_principal', 'tipus_secundari', 'organ_localitzacio',
            'recepcio_confirmada', 'editantId', 'formulariObert', 'missatge',
        ]);

        $this->data_entrada = now()->format('Y-m-d');
        $this->hora_entrada = now()->format('H:i');
        $this->resetValidation();
    }

    public function render(): View
    {
        $cerca = trim($this->cerca);

        return view('livewire.mostres.index', [
            'mostres' => Mostra::query()
                ->when($cerca !== '', function ($query) use ($cerca) {
                    $query->where(function ($q) use ($cerca) {
                        $q->where('identificacio', 'like', "%{$cerca}%")
                          ->orWhere('procedencia', 'like', "%{$cerca}%")
                          ->orWhere('sollicitant', 'like', "%{$cerca}%");
                    });
                })
                ->orderByDesc('id')
                ->paginate(10),
            'consulta' => $this->consultantId === null
                ? null
                : Mostra::query()->find($this->consultantId),
        ]);
    }
}
