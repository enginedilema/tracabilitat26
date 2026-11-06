<?php

namespace Tests\Feature;

use App\Livewire\Mostres\Index;
use App\Models\Mostra;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MostresCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_llistat_necessita_autenticacio(): void
    {
        $this->get('/mostres')->assertRedirect('/login');
    }

    public function test_un_usuari_pot_crear_una_mostra(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(Index::class)
            ->call('crear')
            ->set('identificacio', 'LAB-TEST-001')
            ->set('tipus_mostra', 'citologia')
            ->set('data_entrada', '2026-11-06')
            ->set('hora_entrada', '10:15')
            ->set('procedencia', 'Laboratori de proves')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('mostres', [
            'identificacio' => 'LAB-TEST-001',
            'tipus_mostra' => 'citologia',
        ]);
    }

    public function test_no_permet_identificacions_duplicades(): void
    {
        $this->mostra('LAB-DUPLICAT');

        Livewire::actingAs(User::factory()->create())
            ->test(Index::class)
            ->call('crear')
            ->set('identificacio', 'LAB-DUPLICAT')
            ->set('data_entrada', '2026-11-06')
            ->set('hora_entrada', '10:15')
            ->call('guardar')
            ->assertHasErrors(['identificacio' => 'unique']);
    }

    public function test_pot_editar_una_mostra(): void
    {
        $mostra = $this->mostra('LAB-EDITAR');

        Livewire::actingAs(User::factory()->create())
            ->test(Index::class)
            ->call('editar', $mostra->id)
            ->set('procedencia', 'Nova procedència')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('mostres', [
            'id' => $mostra->id,
            'procedencia' => 'Nova procedència',
        ]);
    }

    public function test_pot_cercar_una_mostra(): void
    {
        $this->mostra('LAB-TROBAR');
        $this->mostra('ALTRE-REGISTRE');

        Livewire::actingAs(User::factory()->create())
            ->test(Index::class)
            ->set('cerca', 'LAB-TROBAR')
            ->assertSee('LAB-TROBAR')
            ->assertDontSee('ALTRE-REGISTRE');
    }

    public function test_la_baixa_no_esborra_el_registre_fisic(): void
    {
        $mostra = $this->mostra('LAB-BAIXA');

        Livewire::actingAs(User::factory()->create())
            ->test(Index::class)
            ->call('eliminar', $mostra->id);

        $this->assertSoftDeleted('mostres', ['id' => $mostra->id]);
    }

    private function mostra(string $identificacio): Mostra
    {
        return Mostra::create([
            'identificacio' => $identificacio,
            'tipus_mostra' => 'citologia',
            'data_entrada' => '2026-11-06',
            'hora_entrada' => '10:15',
            'recepcio_confirmada' => false,
        ]);
    }
}
