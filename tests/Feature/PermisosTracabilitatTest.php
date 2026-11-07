<?php

namespace Tests\Feature;

use App\Livewire\Administracio\UsuarisIndex;
use App\Livewire\Mostres\Index;
use App\Livewire\Mostres\Proces;
use App\Models\Mostra;
use App\Models\MostraProcessament;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PermisosTracabilitatTest extends TestCase
{
    use RefreshDatabase;

    private function usuari(string $rol, bool $actiu = true): User
    {
        return User::factory()->create(['rol' => $rol, 'actiu' => $actiu]);
    }

    private function mostra(): Mostra
    {
        $mostra = Mostra::query()->create([
            'identificacio' => 'LAB-ROL-001', 'tipus_mostra' => 'citologia',
            'data_entrada' => '2026-11-06', 'hora_entrada' => '10:00',
            'recepcio_confirmada' => true,
        ]);
        $mostra->forceFill(['estat' => 'rebuda'])->save();
        return $mostra;
    }

    public function test_un_compte_pendent_o_inactiu_no_pot_veure_mostres(): void
    {
        $this->actingAs($this->usuari('pendent'))->get(route('mostres.index'))->assertForbidden();
        $this->actingAs($this->usuari('tecnic', false))->get(route('mostres.index'))->assertForbidden();
    }

    public function test_tecnic_no_pot_donar_de_baixa_una_mostra(): void
    {
        $mostra = $this->mostra();
        Livewire::actingAs($this->usuari('tecnic'))
            ->test(Index::class)->call('eliminar', $mostra->id)->assertForbidden();
        $this->assertNotSoftDeleted('mostres', ['id' => $mostra->id]);
    }

    public function test_tecnic_no_pot_confirmar_diagnostic_manipulant_livewire(): void
    {
        $mostra = $this->mostra();
        Livewire::actingAs($this->usuari('tecnic'))
            ->test(Proces::class, ['mostra' => $mostra])
            ->set('formulari.diagnostic_confirmat', true)
            ->call('guardar')->assertForbidden();
        $this->assertDatabaseMissing('mostra_processaments', ['mostra_id' => $mostra->id]);
    }

    public function test_patoleg_no_pot_editar_dades_del_processament(): void
    {
        $mostra = $this->mostra();
        Livewire::actingAs($this->usuari('patoleg'))
            ->test(Proces::class, ['mostra' => $mostra])
            ->set('formulari.descripcio_macroscopica', 'Canvi prohibit')
            ->call('guardar')->assertForbidden();
    }

    public function test_patoleg_pot_confirmar_diagnostic_i_arxivar_amb_lliurament(): void
    {
        $mostra = $this->mostra();
        MostraProcessament::query()->create([
            'mostra_id' => $mostra->id, 'lliurament_confirmat' => true,
        ]);
        Livewire::actingAs($this->usuari('patoleg'))
            ->test(Proces::class, ['mostra' => $mostra])
            ->set('formulari.diagnostic_confirmat', true)
            ->call('guardar')->assertHasNoErrors()
            ->set('formulari.arxiu_confirmat', true)
            ->call('guardar')->assertHasNoErrors();
        $this->assertDatabaseHas('mostres', ['id' => $mostra->id, 'estat' => 'arxivada']);
    }

    public function test_només_admin_pot_gestionar_usuaris(): void
    {
        $this->actingAs($this->usuari('tecnic'))->get(route('usuaris.index'))->assertForbidden();
        $this->actingAs($this->usuari('admin'))->get(route('usuaris.index'))->assertOk();
    }

    public function test_admin_no_pot_desactivar_el_seu_propi_compte(): void
    {
        $admin = $this->usuari('admin');
        Livewire::actingAs($admin)->test(UsuarisIndex::class)
            ->set('actius.'.$admin->id, false)
            ->call('actualitzar', $admin->id)
            ->assertHasErrors(['rols']);
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'actiu' => true]);
    }

    public function test_admin_pot_assignar_rol_i_queda_auditat(): void
    {
        $admin = $this->usuari('admin');
        $nou = $this->usuari('pendent', false);
        Livewire::actingAs($admin)->test(UsuarisIndex::class)
            ->set('rols.'.$nou->id, 'patoleg')
            ->set('actius.'.$nou->id, true)
            ->call('actualitzar', $nou->id)->assertHasNoErrors();
        $this->assertDatabaseHas('users', ['id' => $nou->id, 'rol' => 'patoleg', 'actiu' => true]);
        $this->assertDatabaseHas('usuari_auditoria', ['usuari_id' => $nou->id, 'actor_id' => $admin->id]);
    }
}
