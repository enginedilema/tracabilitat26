<?php

namespace Tests\Feature;

use App\Livewire\Mostres\Proces;
use App\Models\Mostra;
use App\Models\MostraProcessament;
use App\Models\User;
use App\Support\ProgresMostra;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProgresMostraTest extends TestCase
{
    use RefreshDatabase;

    public function test_citologia_compta_la_recepcio_i_les_fases_desades(): void
    {
        $mostra = $this->crearMostra('CIT-001', 'citologia');

        Livewire::actingAs(User::factory()->create())
            ->test(Proces::class, ['mostra' => $mostra])
            ->assertViewHas('progres', fn (array $p): bool =>
                $p['completades'] === 1 && $p['total'] === 8 && $p['percentatge'] === 13
            )
            ->set('formulari.descripcio_macroscopica', 'Mostra de prova')
            ->set('formulari.descripcio_confirmada', true)
            ->set('formulari.procediments', ['Citocentrifugat'])
            ->set('formulari.procediment_confirmat', true)
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertViewHas('progres', fn (array $p): bool =>
                $p['completades'] === 3 && $p['total'] === 8 && $p['percentatge'] === 38
            );
    }

    public function test_biopsia_disposa_de_13_fases_i_no_compta_camps_sense_confirmacio(): void
    {
        $mostra = $this->crearMostra('BIO-001', 'biopsia');
        $proces = MostraProcessament::create([
            'mostra_id' => $mostra->id,
            'numero_blocs' => 3,
            'talls_per_bloc' => 'Bloc 1: 4',
            'tallat_macroscopic_confirmat' => false,
            'processador_teixits_confirmat' => true,
            'inclusio_parafina_confirmada' => true,
        ]);

        $progres = ProgresMostra::calcular($mostra, $proces);

        $this->assertSame(13, $progres['total']);
        $this->assertSame(3, $progres['completades']);
        $this->assertSame(23, $progres['percentatge']);
    }

    public function test_el_progres_pot_arribar_al_cent_per_cent(): void
    {
        $mostra = $this->crearMostra('CIT-002', 'citologia');
        $proces = new MostraProcessament([
            'descripcio_confirmada' => true,
            'procediment_confirmat' => true,
            'tincions_confirmades' => true,
            'muntatge_confirmat' => true,
            'lliurament_confirmat' => true,
            'diagnostic_confirmat' => true,
            'arxiu_confirmat' => true,
        ]);

        $progres = ProgresMostra::calcular($mostra, $proces);

        $this->assertSame(8, $progres['completades']);
        $this->assertSame(100, $progres['percentatge']);
    }

    private function crearMostra(string $identificacio, string $tipus): Mostra
    {
        $mostra = Mostra::create([
            'identificacio' => $identificacio,
            'tipus_mostra' => $tipus,
            'data_entrada' => '2026-11-06',
            'hora_entrada' => '10:15',
            'recepcio_confirmada' => true,
        ]);
        $mostra->forceFill(['estat' => 'rebuda'])->save();

        return $mostra;
    }
}
