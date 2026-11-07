<?php

namespace App\Livewire\Mostres;

use App\Models\Mostra;
use App\Models\MostraEsdeveniment;
use App\Models\MostraProcessament;
use App\Services\QrPreimpressioService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $cerca = '';
    public string $filtreTipus = '';
    public string $filtreEstat = '';
    public string $filtreDesDe = '';
    public string $filtreFins = '';
    public bool $formulariObert = false;
    public ?int $editantId = null;
    public ?int $consultantId = null;
    public string $missatge = '';

    public string $qrCodi = '';
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

    public function mount(): void
    {
        $token = request()->query('qr');
        if (is_string($token) && Str::isUuid($token) && Gate::allows('create', Mostra::class)) {
            $this->reiniciarFormulari();
            $this->qrCodi = strtolower($token);
            $this->formulariObert = true;
        }
    }

    public function updatedCerca(): void
    {
        $this->resetPage();
    }

    public function updatedFiltreTipus(): void { $this->resetPage(); }
    public function updatedFiltreEstat(): void { $this->resetPage(); }
    public function updatedFiltreDesDe(): void { $this->resetPage(); }
    public function updatedFiltreFins(): void { $this->resetPage(); }

    public function crear(): void
    {
        Gate::authorize('create', Mostra::class);
        $this->reiniciarFormulari();
        $this->formulariObert = true;
        $this->consultantId = null;
    }

    public function consultar(int $id): void
    {
        $m = Mostra::query()->findOrFail($id);
        Gate::authorize('view', $m);
        $this->consultantId = $m->id;
        $this->formulariObert = false;
        $this->missatge = '';
    }

    public function editar(int $id): void
    {
        $mostra = Mostra::query()->findOrFail($id);
        Gate::authorize('update', $mostra);
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

    public function guardar(QrPreimpressioService $serveiQr): void
    {
        if ($this->editantId === null) {
            Gate::authorize('create', Mostra::class);
        } else {
            Gate::authorize('update', Mostra::query()->findOrFail($this->editantId));
        }

        if ($this->edat === '') {
            $this->edat = null;
        }

        $dades = $this->validate([
            'qrCodi' => [$this->editantId === null ? 'required' : 'nullable', 'string', 'max:512'],
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

        // El codi es valida per separat, no és una columna de la taula mostres.
        unset($dades['qrCodi']);

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

        $missatge = DB::transaction(function () use ($dades, $serveiQr): string {
            if ($this->editantId !== null) {
                $mostra = Mostra::query()->findOrFail($this->editantId);
                $teProces = MostraProcessament::query()->where('mostra_id', $mostra->id)->exists();
                if ($teProces && $dades['tipus_mostra'] !== $mostra->tipus_mostra) {
                    $this->addError('tipus_mostra', 'No es pot canviar el tipus amb un processament iniciat.');
                    return '';
                }
                if ($teProces && ! $dades['recepcio_confirmada']) {
                    $this->addError('recepcio_confirmada', 'No es pot retirar la recepció quan ja hi ha processament.');
                    return '';
                }

                $mostra->fill($dades);
                $camps = array_keys($mostra->getDirty());
                $mostra->save();
                $estatAnterior = $mostra->estat;
                if (! $teProces) {
                    $estatNou = $dades['recepcio_confirmada'] ? 'rebuda' : 'pendent';
                    if ($estatAnterior !== $estatNou) {
                        $mostra->forceFill(['estat' => $estatNou])->save();
                    }
                }

                if ($camps !== [] || $mostra->estat !== $estatAnterior) {
                    MostraEsdeveniment::registrar($mostra, 'mostra_actualitzada', [
                        'camps_modificats' => $camps,
                        'estat_anterior' => $estatAnterior,
                        'estat_nou' => $mostra->estat,
                    ]);
                }
                return 'Mostra actualitzada correctament.';
            }

            $mostra = Mostra::query()->create($dades);
            $mostra->forceFill(['estat' => $dades['recepcio_confirmada'] ? 'rebuda' : 'pendent'])->save();

            // Si no es pot vincular, la transacció reverteix també l'alta de la mostra.
            $etiqueta = $serveiQr->assignar($this->qrCodi, $mostra);

            MostraEsdeveniment::registrar($mostra, 'mostra_registrada', [
                'estat_nou' => $mostra->estat,
            ]);
            MostraEsdeveniment::registrar($mostra, 'qr_assignat', [
                'etiqueta_id' => $etiqueta->id,
                'lot_id' => $etiqueta->lot_id,
            ]);
            return 'Mostra registrada correctament.';
        });

        if ($missatge === '') {
            return;
        }

        $this->reiniciarFormulari();
        $this->resetPage();
        $this->missatge = $missatge;
    }

    public function eliminar(int $id): void
    {
        Gate::authorize('delete', Mostra::query()->findOrFail($id));

        DB::transaction(function () use ($id): void {
            $mostra = Mostra::query()->findOrFail($id);
            MostraEsdeveniment::registrar($mostra, 'mostra_baixa', [
                'estat_anterior' => $mostra->estat,
            ]);
            $mostra->delete();
        });

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
            'qrCodi', 'identificacio', 'tipus_mostra', 'genere', 'edat', 'motiu_sollicitud',
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
        Gate::authorize('viewAny', Mostra::class);
        $cerca = trim($this->cerca);

        return view('livewire.mostres.index', [
            'mostres' => Mostra::query()
                ->when(in_array($this->filtreTipus, ['citologia', 'biopsia'], true),
                    fn ($query) => $query->where('tipus_mostra', $this->filtreTipus))
                ->when(in_array($this->filtreEstat, ['pendent', 'rebuda', 'en_proces', 'finalitzada', 'arxivada'], true),
                    fn ($query) => $query->where('estat', $this->filtreEstat))
                ->when(preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->filtreDesDe),
                    fn ($query) => $query->whereDate('data_entrada', '>=', $this->filtreDesDe))
                ->when(preg_match('/^\d{4}-\d{2}-\d{2}$/', $this->filtreFins),
                    fn ($query) => $query->whereDate('data_entrada', '<=', $this->filtreFins))
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
