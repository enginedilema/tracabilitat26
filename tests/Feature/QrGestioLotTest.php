<?php

namespace Tests\Feature;

use App\Livewire\Etiquetes\GestioLot;
use App\Models\Mostra;
use App\Models\QrLot;
use App\Models\User;
use App\Services\QrPreimpressioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class QrGestioLotTest extends TestCase
{
    use RefreshDatabase;

    private function administrar(): User
    {
        return User::factory()->create(['rol' => 'admin', 'actiu' => true]);
    }

    public function test_administrador_pot_consultar_lot_i_tecnic_no(): void
    {
        $this->actingAs($this->administrar());
        $lot = app(QrPreimpressioService::class)->crearLot(2);

        $this->get(route('etiquetes.lot', $lot))->assertOk()->assertSee('Gestió del lot');
        $this->get(route('etiquetes.lot', ['lot' => 999999]))->assertNotFound();

        $this->actingAs(User::factory()->create(['rol' => 'tecnic', 'actiu' => true]));
        $this->get(route('etiquetes.lot', $lot))->assertForbidden();
    }

    public function test_anullacio_individual_registra_actor_motiu_i_data_i_bloqueja_qr(): void
    {
        $admin = $this->administrar();
        $this->actingAs($admin);
        $lot = app(QrPreimpressioService::class)->crearLot(2);
        $etiqueta = $lot->etiquetes()->firstOrFail();

        Livewire::actingAs($admin)->test(GestioLot::class, ['lotId' => $lot->id])
            ->call('prepararEtiqueta', $etiqueta->id)
            ->set('motiuAnullacio', 'Etiqueta física deteriorada')
            ->set('confirmat', true)
            ->call('confirmarAnullacio')
            ->assertHasNoErrors();

        $etiqueta->refresh();
        $this->assertSame('anullat', $etiqueta->estat);
        $this->assertSame($admin->id, $etiqueta->anullat_per);
        $this->assertSame('Etiqueta física deteriorada', $etiqueta->motiu_anullacio);
        $this->assertNotNull($etiqueta->anullat_at);
        $this->assertNull($etiqueta->mostra_id);
        $this->assertSame(1, $lot->etiquetes()->where('estat', 'disponible')->count());
        $this->get(route('mostres.qr.resolve', $etiqueta->token))->assertStatus(410);
    }

    public function test_no_es_pot_anullar_un_qr_ja_assignat(): void
    {
        $this->actingAs($this->administrar());
        $servei = app(QrPreimpressioService::class);
        $lot = $servei->crearLot(1);
        $etiqueta = $lot->etiquetes()->firstOrFail();
        $mostra = Mostra::create([
            'identificacio' => 'QR-GESTIO-001', 'tipus_mostra' => 'citologia',
            'data_entrada' => '2026-11-07', 'hora_entrada' => '11:00',
            'recepcio_confirmada' => true,
        ]);
        $servei->assignar($etiqueta->token, $mostra);

        try {
            $servei->anullarEtiqueta($lot->id, $etiqueta->id, 'Etiquetatge incorrecte');
            $this->fail('No es pot anul·lar una etiqueta assignada.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('motiuAnullacio', $e->errors());
        }

        $this->assertDatabaseHas('qr_etiquetes', [
            'id' => $etiqueta->id, 'estat' => 'assignat', 'mostra_id' => $mostra->id,
        ]);
    }

    public function test_anullacio_massiva_conserva_els_qr_assignats(): void
    {
        $admin = $this->administrar();
        $this->actingAs($admin);
        $servei = app(QrPreimpressioService::class);
        $lot = $servei->crearLot(3);
        $etiquetaAssignada = $lot->etiquetes()->firstOrFail();
        $mostra = Mostra::create([
            'identificacio' => 'QR-GESTIO-002', 'tipus_mostra' => 'biopsia',
            'data_entrada' => '2026-11-07', 'hora_entrada' => '11:01',
            'recepcio_confirmada' => true,
        ]);
        $servei->assignar($etiquetaAssignada->token, $mostra);

        Livewire::actingAs($admin)->test(GestioLot::class, ['lotId' => $lot->id])
            ->call('prepararLot')
            ->set('motiuAnullacio', 'Full de paper perdut')
            ->set('confirmat', true)
            ->call('confirmarAnullacio')
            ->assertHasNoErrors();

        $this->assertSame(2, $lot->etiquetes()->where('estat', 'anullat')->count());
        $this->assertSame(1, $lot->etiquetes()->where('estat', 'assignat')->count());
        $this->assertSame(0, $lot->etiquetes()->where('estat', 'disponible')->count());
        $this->get(route('mostres.qr.resolve', $etiquetaAssignada->token))
            ->assertRedirect(route('mostres.proces', $mostra));
    }

    public function test_anullacio_exigeix_motiu_i_confirmacio(): void
    {
        $admin = $this->administrar();
        $this->actingAs($admin);
        $lot = app(QrPreimpressioService::class)->crearLot(1);
        $etiqueta = $lot->etiquetes()->firstOrFail();

        Livewire::actingAs($admin)->test(GestioLot::class, ['lotId' => $lot->id])
            ->call('prepararEtiqueta', $etiqueta->id)
            ->set('motiuAnullacio', 'Curta')
            ->call('confirmarAnullacio')
            ->assertHasErrors(['motiuAnullacio', 'confirmat']);

        $this->assertDatabaseHas('qr_etiquetes', ['id' => $etiqueta->id, 'estat' => 'disponible']);
    }

    public function test_reimpressio_nomes_inclou_qr_disponibles(): void
    {
        $this->actingAs($this->administrar());
        $servei = app(QrPreimpressioService::class);
        $lot = $servei->crearLot(3);
        $etiquetes = $lot->etiquetes()->orderBy('id')->get();
        $servei->anullarEtiqueta($lot->id, $etiquetes[0]->id, 'Etiqueta perduda');
        $mostra = Mostra::create([
            'identificacio' => 'QR-GESTIO-003', 'tipus_mostra' => 'citologia',
            'data_entrada' => '2026-11-07', 'hora_entrada' => '11:02',
            'recepcio_confirmada' => true,
        ]);
        $servei->assignar($etiquetes[1]->token, $mostra);

        $resposta = $this->get(route('etiquetes.a4', $lot))->assertOk();
        $this->assertSame(1, substr_count($resposta->getContent(), '<svg'));
        $resposta->assertSee('1 codis disponibles per assignar');
    }

    public function test_lot_sense_disponibles_no_imprimeix_cap_qr(): void
    {
        $this->actingAs($this->administrar());
        $servei = app(QrPreimpressioService::class);
        $lot = $servei->crearLot(1);
        $servei->anullarDisponiblesLot($lot->id, 'No es necessita el full');

        $resposta = $this->get(route('etiquetes.a4', $lot))->assertOk();
        $this->assertSame(0, substr_count($resposta->getContent(), '<svg'));
        $resposta->assertSee('No hi ha etiquetes disponibles');
    }
}
