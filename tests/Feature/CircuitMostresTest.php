<?php

namespace Tests\Feature;

use App\Livewire\Mostres\Index;
use App\Livewire\Mostres\Proces;
use App\Models\Mostra;
use App\Models\MostraEsdeveniment;
use App\Models\MostraProcessament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CircuitMostresTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_ruta_del_circuit_requereix_login(): void
    {
        $mostra = $this->mostra('LAB-100', 'citologia');
        $this->get(route('mostres.proces', $mostra))->assertRedirect('/login');
    }

    public function test_pot_iniciar_processament_de_citologia_i_registrar_actor(): void
    {
        $usuari = User::factory()->create();
        $mostra = $this->mostra('LAB-101', 'citologia');

        Livewire::actingAs($usuari)->test(Proces::class, ['mostra' => $mostra])
            ->set('formulari.descripcio_macroscopica', 'Mostra de prova')
            ->set('formulari.descripcio_confirmada', true)
            ->set('formulari.procediments', ['Citocentrifugat'])
            ->set('formulari.procediment_confirmat', true)
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('mostres', ['id' => $mostra->id, 'estat' => 'en_proces']);
        $proces = MostraProcessament::where('mostra_id', $mostra->id)->firstOrFail();
        $this->assertSame(['Citocentrifugat'], $proces->procediments);
        $this->assertTrue($proces->procediment_confirmat);
        $this->assertDatabaseHas('mostra_esdeveniments', [
            'mostra_id' => $mostra->id, 'usuari_id' => $usuari->id,
            'accio' => 'processament_actualitzat',
        ]);
    }

    public function test_no_permet_arxivar_sense_diagnostic(): void
    {
        $mostra = $this->mostra('LAB-102', 'citologia');

        Livewire::actingAs(User::factory()->create())
            ->test(Proces::class, ['mostra' => $mostra])
            ->set('formulari.arxiu_confirmat', true)
            ->call('guardar')
            ->assertHasErrors(['formulari.arxiu_confirmat']);

        $this->assertDatabaseMissing('mostra_processaments', ['mostra_id' => $mostra->id]);
    }

    public function test_no_permet_processar_sense_recepcio_confirmada(): void
    {
        $mostra = Mostra::create([
            'identificacio' => 'LAB-103', 'tipus_mostra' => 'biopsia',
            'data_entrada' => '2026-11-06', 'hora_entrada' => '09:30',
            'recepcio_confirmada' => false,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(Proces::class, ['mostra' => $mostra])
            ->call('guardar')
            ->assertHasErrors(['formulari.descripcio_macroscopica']);
    }

    public function test_biopsia_pot_conservar_el_registre_de_blocs_i_laminetes(): void
    {
        $mostra = $this->mostra('LAB-104', 'biopsia');

        Livewire::actingAs(User::factory()->create())
            ->test(Proces::class, ['mostra' => $mostra])
            ->set('formulari.numero_blocs', '4')
            ->set('formulari.talls_per_bloc', 'Bloc 1: 4; Bloc 2: 1')
            ->set('formulari.laminetes_per_bloc', 'Bloc 1: 3; Bloc 2: 2')
            ->set('formulari.tallat_macroscopic_confirmat', true)
            ->set('formulari.tincions', ['Hematoxilina-eosina'])
            ->call('guardar')
            ->assertHasNoErrors();

        $proces = MostraProcessament::where('mostra_id', $mostra->id)->firstOrFail();
        $this->assertSame(4, $proces->numero_blocs);
        $this->assertSame('Bloc 1: 3; Bloc 2: 2', $proces->laminetes_per_bloc);
        $this->assertSame(['Hematoxilina-eosina'], $proces->tincions);
    }

    public function test_es_pot_arribar_a_finalitzada_i_arxivada(): void
    {
        $mostra = $this->mostra('LAB-105', 'citologia');
        $usuari = User::factory()->create();

        Livewire::actingAs($usuari)->test(Proces::class, ['mostra' => $mostra])
            ->set('formulari.lliurament_confirmat', true)
            ->set('formulari.diagnostic_confirmat', true)
            ->call('guardar')
            ->assertHasNoErrors()
            ->set('formulari.arxiu_confirmat', true)
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('mostres', ['id' => $mostra->id, 'estat' => 'arxivada']);
        $this->assertSame(2, MostraEsdeveniment::where('mostra_id', $mostra->id)->count());
    }

    public function test_filtrar_per_estat_i_crear_esdeveniment_en_editar(): void
    {
        $mostra = $this->mostra('LAB-106', 'citologia');
        $pendent = Mostra::create([
            'identificacio' => 'LAB-PENDENT', 'tipus_mostra' => 'biopsia',
            'data_entrada' => '2026-11-06', 'hora_entrada' => '09:30',
            'recepcio_confirmada' => false,
        ]);

        Livewire::actingAs(User::factory()->create())
            ->test(Index::class)
            ->set('filtreEstat', 'rebuda')
            ->assertSee('LAB-106')
            ->assertDontSee('LAB-PENDENT')
            ->call('editar', $mostra->id)
            ->set('sollicitant', 'Unitat de proves')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('mostra_esdeveniments', [
            'mostra_id' => $mostra->id, 'accio' => 'mostra_actualitzada',
        ]);
    }

    private function mostra(string $identificacio, string $tipus): Mostra
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
