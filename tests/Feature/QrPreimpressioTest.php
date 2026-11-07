<?php

namespace Tests\Feature;

use App\Livewire\Etiquetes\GeneradorEtiquetes;
use App\Livewire\Mostres\Index;
use App\Models\Mostra;
use App\Models\QrEtiqueta;
use App\Models\QrLot;
use App\Models\User;
use App\Services\MostraQrService;
use App\Services\QrPreimpressioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class QrPreimpressioTest extends TestCase
{
    use RefreshDatabase;

    private function compte(string $rol = 'admin'): User
    {
        return User::factory()->create(['rol' => $rol, 'actiu' => true]);
    }

    public function test_administrador_genera_deu_qr_sense_assignar_i_imprimeix_a4(): void
    {
        $admin = $this->compte();
        $this->actingAs($admin);

        Livewire::test(GeneradorEtiquetes::class)->set('quantitat', 10)->call('generar')->assertHasNoErrors();

        $lot = QrLot::query()->firstOrFail();
        $this->assertSame(10, $lot->etiquetes()->count());
        $this->assertSame(10, $lot->etiquetes()->where('estat', 'disponible')->count());
        $this->assertSame(10, $lot->etiquetes()->whereNull('mostra_id')->count());
        $this->assertSame(10, $lot->etiquetes()->distinct('token')->count('token'));

        $this->get(route('etiquetes.a4', $lot))
            ->assertOk()
            ->assertSee('<svg', false)
            ->assertDontSee('DADES_CLINIQUES');
    }

    public function test_només_admin_pot_generar_i_imprimir_lots(): void
    {
        $lot = QrLot::create(['creat_per' => null, 'quantitat' => 1]);
        $this->get(route('etiquetes.index'))->assertRedirect('/login');
        $this->actingAs($this->compte('tecnic'));
        $this->get(route('etiquetes.index'))->assertForbidden();
        $this->get(route('etiquetes.a4', $lot))->assertForbidden();
    }

    public function test_lectura_de_qr_disponible_obre_alta_sense_assignar_lo(): void
    {
        $this->actingAs($this->compte('admin'));
        $lot = app(QrPreimpressioService::class)->crearLot(1);
        $etiqueta = $lot->etiquetes()->firstOrFail();

        $this->get(route('mostres.qr.resolve', $etiqueta->token))
            ->assertRedirect(route('mostres.index', ['qr' => $etiqueta->token]));
        $this->assertDatabaseHas('qr_etiquetes', [
            'id' => $etiqueta->id,
            'estat' => 'disponible',
            'mostra_id' => null,
        ]);
    }

    public function test_alta_assigna_etiqueta_unica_a_mostra_i_l_antic_lector_la_resol(): void
    {
        $admin = $this->compte();
        $this->actingAs($admin);
        $etiqueta = app(QrPreimpressioService::class)->crearLot(1)->etiquetes()->firstOrFail();

        Livewire::actingAs($admin)->test(Index::class)
            ->call('crear')
            ->set('qrCodi', $etiqueta->token)
            ->set('identificacio', 'LAB-PRE-001')
            ->set('tipus_mostra', 'citologia')
            ->set('data_entrada', '2026-11-07')
            ->set('hora_entrada', '10:00')
            ->call('guardar')
            ->assertHasNoErrors();

        $mostra = Mostra::query()->where('identificacio', 'LAB-PRE-001')->firstOrFail();
        $this->assertDatabaseHas('qr_etiquetes', [
            'token' => $etiqueta->token,
            'estat' => 'assignat',
            'mostra_id' => $mostra->id,
        ]);
        $this->assertSame($etiqueta->token, app(MostraQrService::class)->token($mostra));
        $this->get(route('mostres.qr.resolve', $etiqueta->token))
            ->assertRedirect(route('mostres.proces', $mostra));
    }

    public function test_un_qr_no_es_pot_assignar_a_dues_mostres(): void
    {
        $this->actingAs($this->compte());
        $servei = app(QrPreimpressioService::class);
        $etiqueta = $servei->crearLot(1)->etiquetes()->firstOrFail();
        $primera = Mostra::create([
            'identificacio' => 'LAB-PRE-101', 'tipus_mostra' => 'citologia',
            'data_entrada' => '2026-11-07', 'hora_entrada' => '10:00',
            'recepcio_confirmada' => true,
        ]);
        $segona = Mostra::create([
            'identificacio' => 'LAB-PRE-102', 'tipus_mostra' => 'citologia',
            'data_entrada' => '2026-11-07', 'hora_entrada' => '10:01',
            'recepcio_confirmada' => true,
        ]);
        $servei->assignar($etiqueta->token, $primera);

        try {
            $servei->assignar($etiqueta->token, $segona);
            $this->fail('S’hauria d’impedir una segona assignació.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('qrCodi', $e->errors());
        }
        $this->assertDatabaseHas('qr_etiquetes', ['id' => $etiqueta->id, 'mostra_id' => $primera->id]);
    }

    public function test_els_qr_antics_de_v06_es_mantenen_valids(): void
    {
        $this->actingAs($this->compte());
        $mostra = Mostra::create([
            'identificacio' => 'LAB-ANTIC', 'tipus_mostra' => 'biopsia',
            'data_entrada' => '2026-11-07', 'hora_entrada' => '10:00',
            'recepcio_confirmada' => true,
        ]);
        $token = app(MostraQrService::class)->token($mostra);
        $this->assertTrue(Str::isUuid($token));
        $this->get(route('mostres.qr.resolve', $token))
            ->assertRedirect(route('mostres.proces', $mostra));
    }
}
