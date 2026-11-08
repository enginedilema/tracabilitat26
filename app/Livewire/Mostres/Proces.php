<?php

namespace App\Livewire\Mostres;

use App\Models\Mostra;
use App\Models\MostraEsdeveniment;
use App\Models\MostraProcessament;
use App\Support\ProgresMostra;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Proces extends Component
{
    public const PROCEDIMENTS_CITOLOGIA = [
        'Extensió material', 'Citocentrifugat', 'Citologia líquida',
        "Rentat d'agulla", 'Bloc cel·lular', 'Thin prep',
    ];

    public const TINCIONS_CITOLOGIA = [
        'Papanicolaou', 'Diff-Quick', 'Giemsa', 'PAS', 'Metenamina-plata',
    ];

    public const TINCIONS_BIOPSIA = [
        'Hematoxilina-eosina', 'Giemsa', 'PAS', 'Blau Alcian',
        'Metenamina-plata', 'Roig Congo', 'Tricròmic de Masson',
        'Reticulina', 'Orceina', 'Oil Red-0', 'Gordon and Sweets',
        'Ziehl-Neelsen', 'Gram',
    ];

    #[Locked]
    public int $mostraId;

    public array $formulari = [];

    public string $missatge = '';

    public function mount(Mostra $mostra): void
    {
        Gate::authorize('view', $mostra);
        $this->mostraId = $mostra->id;
        $anterior = MostraProcessament::query()->where('mostra_id', $mostra->id)->first();
        $this->formulari = array_replace(
            $this->valorsInicials(),
            $anterior ? $anterior->only(array_keys($this->valorsInicials())) : [],
        );
        // Wire:model necessita valors que coincideixin amb el tipus del control.
        $this->formulari['procediments'] ??= [];
        $this->formulari['tincions'] ??= [];
        $this->formulari['numero_blocs'] ??= '';
    }

    public function guardar(): void
    {
        $mostra = Mostra::query()->findOrFail($this->mostraId);
        Gate::authorize('view', $mostra);
        abort_unless(Gate::any(['process', 'diagnose'], $mostra), 403);

        if (! $mostra->recepcio_confirmada) {
            throw ValidationException::withMessages([
                'formulari.descripcio_macroscopica' => 'Cal confirmar la recepció de la mostra abans del processament.',
            ]);
        }

        $dades = $this->validate($this->regles($mostra))['formulari'];
        $dades['numero_blocs'] = isset($dades['numero_blocs']) && $dades['numero_blocs'] !== ''
            ? (int) $dades['numero_blocs'] : null;
        // Comprovem els camps sensibles amb les dades REALS de la BD.
        // Un usuari no pot elevar permisos canviant propietats Livewire.
        $this->protegirCanvis($mostra, $dades);
        $esCitologia = $mostra->tipus_mostra === 'citologia';
        if ($esCitologia) {
            unset($dades['numero_blocs']);
        }
        $lliuramentFet = $esCitologia
            ? $dades['lliurament_confirmat']
            : $dades['lliurament_patoleg_confirmat'];

        if ($dades['diagnostic_confirmat'] && ! $lliuramentFet) {
            throw ValidationException::withMessages([
                'formulari.diagnostic_confirmat' => 'Cal registrar el lliurament abans del diagnòstic.',
            ]);
        }

        if ($dades['arxiu_confirmat'] && ! $dades['diagnostic_confirmat']) {
            throw ValidationException::withMessages([
                'formulari.arxiu_confirmat' => 'No es pot arxivar una mostra sense el diagnòstic confirmat.',
            ]);
        }

        if ($dades['descripcio_confirmada'] && blank($dades['descripcio_macroscopica'])) {
            throw ValidationException::withMessages([
                'formulari.descripcio_macroscopica' => 'Escriu la descripció macroscòpica abans de confirmar-la.',
            ]);
        }

        if ($esCitologia && $dades['procediment_confirmat'] && count($dades['procediments']) === 0) {
            throw ValidationException::withMessages([
                'formulari.procediments' => 'Selecciona com a mínim un procediment.',
            ]);
        }

        if ($dades['tincions_confirmades'] && count($dades['tincions']) === 0) {
            throw ValidationException::withMessages([
                'formulari.tincions' => 'Selecciona com a mínim una tinció.',
            ]);
        }

        if (! $esCitologia && $dades['tallat_macroscopic_confirmat'] && blank($dades['numero_blocs'])) {
            throw ValidationException::withMessages([
                'formulari.numero_blocs' => 'Indica el nombre de blocs abans de confirmar el tallat.',
            ]);
        }

        DB::transaction(function () use ($mostra, $dades): void {
            $registre = MostraProcessament::query()->firstOrNew(['mostra_id' => $mostra->id]);
            $registre->fill($dades);
            $canvis = array_keys($registre->getDirty());
            $canvis = array_values(array_diff($canvis, ['mostra_id', 'updated_at', 'created_at']));
            $registre->save();

            $estatAnterior = $mostra->estat;
            $estatNou = $this->calcularEstat($mostra, $registre);
            if ($estatNou !== $estatAnterior) {
                $mostra->forceFill(['estat' => $estatNou])->save();
            }

            if ($canvis !== [] || $estatNou !== $estatAnterior) {
                MostraEsdeveniment::registrar($mostra, 'processament_actualitzat', [
                    'camps_modificats' => $canvis,
                    'estat_anterior' => $estatAnterior,
                    'estat_nou' => $estatNou,
                ]);
            }
        });

        $this->missatge = 'Processament desat i historial actualitzat.';
    }

    private function protegirCanvis(Mostra $mostra, array $dades): void
    {
        $anterior = MostraProcessament::query()->where('mostra_id', $mostra->id)->first();
        $original = array_replace(
            $this->valorsInicials(),
            $anterior ? $anterior->only(array_keys($this->valorsInicials())) : [],
        );

        foreach ($dades as $camp => $nouValor) {
            $esCampDiagnostic = in_array($camp, ['diagnostic_confirmat', 'arxiu_confirmat'], true);
            $permes = $esCampDiagnostic
                ? Gate::allows('diagnose', $mostra)
                : Gate::allows('process', $mostra);

            if (! $permes && $this->valorComparable($nouValor) !== $this->valorComparable($original[$camp] ?? null)) {
                abort(403, 'No tens permisos per modificar aquesta fase.');
            }
        }
    }

    private function valorComparable(mixed $valor): mixed
    {
        if ($valor === '' || $valor === null) {
            return null;
        }
        if (is_array($valor) && $valor === []) {
            return null;
        }
        if (is_bool($valor)) {
            return $valor;
        }
        if (is_int($valor) || is_float($valor)) {
            return (string) $valor;
        }

        return $valor;
    }

    private function regles(Mostra $mostra): array
    {
        $regles = [
            'formulari' => ['required', 'array'],
            'formulari.descripcio_macroscopica' => ['nullable', 'string', 'max:20000'],
            'formulari.descripcio_confirmada' => ['required', 'boolean'],
            'formulari.tincions' => ['present', 'array'],
            'formulari.tincions_confirmades' => ['required', 'boolean'],
            'formulari.muntatge_confirmat' => ['required', 'boolean'],
            'formulari.diagnostic_confirmat' => ['required', 'boolean'],
            'formulari.arxiu_confirmat' => ['required', 'boolean'],
            'formulari.incidencies' => ['nullable', 'string', 'max:20000'],
        ];

        if ($mostra->tipus_mostra === 'citologia') {
            return $regles + [
                'formulari.procediments' => ['present', 'array'],
                'formulari.procediments.*' => [Rule::in(self::PROCEDIMENTS_CITOLOGIA)],
                'formulari.procediment_confirmat' => ['required', 'boolean'],
                'formulari.tincions.*' => [Rule::in(self::TINCIONS_CITOLOGIA)],
                'formulari.lliurament_confirmat' => ['required', 'boolean'],
            ];
        }

        return $regles + [
            'formulari.tincions.*' => [Rule::in(self::TINCIONS_BIOPSIA)],
            'formulari.numero_blocs' => ['nullable', 'integer', 'between:1,100000'],
            'formulari.talls_per_bloc' => ['nullable', 'string', 'max:10000'],
            'formulari.informacio_tincions' => ['nullable', 'string', 'max:10000'],
            'formulari.tallat_macroscopic_confirmat' => ['required', 'boolean'],
            'formulari.processador_teixits_confirmat' => ['required', 'boolean'],
            'formulari.inclusio_parafina_confirmada' => ['required', 'boolean'],
            'formulari.laminetes_per_bloc' => ['nullable', 'string', 'max:10000'],
            'formulari.confeccio_blocs_confirmada' => ['required', 'boolean'],
            'formulari.lliurament_talls_confirmat' => ['required', 'boolean'],
            'formulari.tincio_confirmada' => ['required', 'boolean'],
            'formulari.lliurament_patoleg_confirmat' => ['required', 'boolean'],
        ];
    }

    private function calcularEstat(Mostra $mostra, MostraProcessament $registre): string
    {
        if (! $mostra->recepcio_confirmada) {
            return 'pendent';
        }
        if ($registre->arxiu_confirmat) {
            return 'arxivada';
        }
        if ($registre->diagnostic_confirmat) {
            return 'finalitzada';
        }
        $camps = $mostra->tipus_mostra === 'citologia'
            ? ['descripcio_confirmada', 'procediment_confirmat', 'tincions_confirmades', 'muntatge_confirmat', 'lliurament_confirmat']
            : ['descripcio_confirmada', 'tallat_macroscopic_confirmat', 'processador_teixits_confirmat', 'inclusio_parafina_confirmada', 'confeccio_blocs_confirmada', 'lliurament_talls_confirmat', 'tincio_confirmada', 'muntatge_confirmat', 'lliurament_patoleg_confirmat'];

        foreach ($camps as $camp) {
            if ($registre->{$camp}) {
                return 'en_proces';
            }
        }

        return 'rebuda';
    }

    private function valorsInicials(): array
    {
        return [
            'descripcio_macroscopica' => '', 'descripcio_confirmada' => false,
            'procediments' => [], 'procediment_confirmat' => false,
            'tincions' => [], 'tincions_confirmades' => false,
            'muntatge_confirmat' => false, 'diagnostic_confirmat' => false,
            'arxiu_confirmat' => false, 'incidencies' => '',
            'lliurament_confirmat' => false, 'numero_blocs' => '',
            'talls_per_bloc' => '', 'informacio_tincions' => '',
            'tallat_macroscopic_confirmat' => false,
            'processador_teixits_confirmat' => false,
            'inclusio_parafina_confirmada' => false,
            'laminetes_per_bloc' => '', 'confeccio_blocs_confirmada' => false,
            'lliurament_talls_confirmat' => false, 'tincio_confirmada' => false,
            'lliurament_patoleg_confirmat' => false,
        ];
    }

    public function render(): View
    {
        $mostra = Mostra::query()->findOrFail($this->mostraId);
        Gate::authorize('view', $mostra);
        $procesDesat = MostraProcessament::query()
            ->where('mostra_id', $mostra->id)->first();
        $progres = ProgresMostra::calcular($mostra, $procesDesat);
        $historial = MostraEsdeveniment::query()
            ->where('mostra_id', $mostra->id)
            ->orderByDesc('id')->limit(100)->get();

        return view('livewire.mostres.proces', compact('mostra', 'historial', 'progres'));
    }
}
