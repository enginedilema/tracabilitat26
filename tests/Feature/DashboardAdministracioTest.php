<?php

namespace Tests\Feature;

use App\Models\Mostra;
use App\Models\MostraEsdeveniment;
use App\Models\MostraProcessament;
use App\Models\User;
use App\Support\DadesDashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAdministracioTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_dashboard_requereix_iniciar_sessio(): void
    {
        $this->get(route('dashboard'))->assertRedirect('/login');
    }

    public function test_el_dashboard_carrega_els_indicadors_i_les_mostres(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'admin', 'actiu' => true]));
        $this->mostra('LAB-DASH-001', 'citologia', 'rebuda');

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Centre de control')
            ->assertSee('Mostres totals')
            ->assertSee('Estat del sistema')
            ->assertSee('LAB-DASH-001');
    }

    public function test_els_recomptes_exclouen_baixes_logiques(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'admin', 'actiu' => true]));

        $this->mostra('LAB-DASH-002', 'citologia', 'rebuda');
        $this->mostra('LAB-DASH-003', 'biopsia', 'en_proces');
        $this->mostra('LAB-DASH-004', 'citologia', 'finalitzada');
        $baixa = $this->mostra('LAB-DASH-005', 'biopsia', 'pendent');
        $baixa->delete();

        $resum = app(DadesDashboard::class)->obtenir();

        $this->assertSame(3, $resum['total']);
        $this->assertSame(2, $resum['citologies']);
        $this->assertSame(1, $resum['biopsies']);
        $this->assertSame(1, $resum['enProces']);
        $this->assertSame(1, $resum['finalitzades']);
        $this->assertSame(0, $resum['pendents']);
        $this->assertSame(3, $resum['avui']);
    }

    public function test_mostra_el_progres_i_el_moviment_recent(): void
    {
        $this->actingAs(User::factory()->create(['name' => 'Usuari de prova', 'rol' => 'admin', 'actiu' => true]));
        $mostra = $this->mostra('LAB-DASH-006', 'citologia', 'en_proces');

        MostraProcessament::query()->create([
            'mostra_id' => $mostra->id,
            'descripcio_confirmada' => true,
            'procediments' => ['Citocentrifugat'],
            'procediment_confirmat' => true,
        ]);
        MostraEsdeveniment::registrar($mostra, 'processament_actualitzat');

        $resum = app(DadesDashboard::class)->obtenir();

        $this->assertSame('LAB-DASH-006', $resum['recents'][0]['identificacio']);
        $this->assertSame(38, $resum['recents'][0]['percentatge']);
        $this->assertSame('3/8', $resum['recents'][0]['fases']);
        $this->assertSame('Processament modificat', $resum['activitat'][0]['accio']);
        $this->assertSame('Usuari de prova', $resum['activitat'][0]['usuari']);
        $this->assertSame(1, $resum['sistema']['accions24h']);
        $this->assertTrue($resum['sistema']['baseDadesOperativa']);
        $this->assertCount(7, $resum['setmana']);
    }

    private function mostra(string $identificacio, string $tipus, string $estat): Mostra
    {
        $mostra = Mostra::query()->create([
            'identificacio' => $identificacio,
            'tipus_mostra' => $tipus,
            'data_entrada' => today()->format('Y-m-d'),
            'hora_entrada' => '09:00',
            'recepcio_confirmada' => true,
        ]);
        $mostra->forceFill(['estat' => $estat])->save();

        return $mostra;
    }
}
