<?php

namespace Tests\Feature;

use App\Livewire\Mostres\SubstituirQr;
use App\Models\Mostra;
use App\Models\MostraEsdeveniment;
use App\Models\QrEtiqueta;
use App\Models\QrSubstitucio;
use App\Models\User;
use App\Services\MostraQrService;
use App\Services\QrPreimpressioService;
use App\Services\QrSubstitucioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class QrSubstitucioTest extends TestCase
{
    use RefreshDatabase;

    private function usuari(string $rol = 'admin'): User
    {
        return User::factory()->create(['rol' => $rol, 'actiu' => true]);
    }

    private function mostra(string $id = 'LAB-QR-100'): Mostra
    {
        return Mostra::create([
            'identificacio' => $id,
            'tipus_mostra' => 'citologia',
            'data_entrada' => '2026-11-07',
            'hora_entrada' => '10:00',
            'recepcio_confirmada' => true,
        ]);
    }

    private function disponible(): QrEtiqueta
    {
        return app(QrPreimpressioService::class)->crearLot(1)->etiquetes()->firstOrFail();
    }

    public function test_pantalla_exigeix_login_i_permisos_d_administrador(): void
    {
        $mostra = $this->mostra();
        $this->get(route('mostres.qr.substituir', $mostra))->assertRedirect('/login');
        $this->actingAs($this->usuari('tecnic'));
        $this->get(route('mostres.qr.substituir', $mostra))->assertForbidden();
        $this->actingAs($this->usuari('admin'));
        $this->get(route('mostres.qr.substituir', $mostra))->assertOk()->assertSee('Substitució controlada');
        $this->get(route('mostres.qr.substituir', ['mostra' => 999999]))->assertNotFound();
    }

    public function test_substitucio_conserva_historial_i_invalida_el_qr_anterior(): void
    {
        $admin = $this->usuari();
        $this->actingAs($admin);
        $mostra = $this->mostra();
        $antic = $this->disponible();
        $nou = $this->disponible();
        app(QrPreimpressioService::class)->assignar($antic->token, $mostra);

        Livewire::actingAs($admin)->test(SubstituirQr::class, ['mostraId' => $mostra->id])
            ->set('qrNou', $nou->token)
            ->set('motiu', 'Etiqueta antiga deteriorada')
            ->set('confirmat', true)
            ->call('confirmar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('qr_etiquetes', [
            'id' => $antic->id, 'estat' => 'anullat', 'mostra_id' => null,
            'anullat_per' => $admin->id,
        ]);
        $this->assertDatabaseHas('qr_etiquetes', [
            'id' => $nou->id, 'estat' => 'assignat', 'mostra_id' => $mostra->id,
        ]);
        $this->assertDatabaseHas('qr_substitucions', [
            'mostra_id' => $mostra->id,
            'etiqueta_antiga_id' => $antic->id,
            'etiqueta_nova_id' => $nou->id,
            'substituit_per' => $admin->id,
            'motiu' => 'Etiqueta antiga deteriorada',
        ]);
        $this->assertSame(1, QrEtiqueta::where('mostra_id', $mostra->id)->where('estat', 'assignat')->count());
        $this->assertSame($nou->token, app(MostraQrService::class)->token($mostra));
        $this->get(route('mostres.qr.resolve', $antic->token))->assertStatus(410);
        $this->get(route('mostres.qr.resolve', $nou->token))->assertRedirect(route('mostres.proces', $mostra));
        $this->assertSame(1, MostraEsdeveniment::where('mostra_id', $mostra->id)->where('accio', 'qr_substituit')->count());
    }

    public function test_no_accepta_codi_ja_assignat_i_no_canvia_el_qr_original(): void
    {
        $this->actingAs($this->usuari());
        $servei = app(QrPreimpressioService::class);
        $mostraA = $this->mostra('LAB-A');
        $mostraB = $this->mostra('LAB-B');
        $antic = $this->disponible();
        $ocupat = $this->disponible();
        $servei->assignar($antic->token, $mostraA);
        $servei->assignar($ocupat->token, $mostraB);

        try {
            app(QrSubstitucioService::class)->substituir($mostraA, $ocupat->token, 'Etiqueta deteriorada');
            $this->fail('S’hauria d’impedir assignar un codi ocupat.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('qrNou', $e->errors());
        }
        $this->assertDatabaseHas('qr_etiquetes', [
            'id' => $antic->id, 'estat' => 'assignat', 'mostra_id' => $mostraA->id,
        ]);
        $this->assertSame(0, QrSubstitucio::count());
    }

    public function test_un_tecnic_no_pot_cridar_el_servei_ni_obrir_pantalla(): void
    {
        $admin = $this->usuari();
        $this->actingAs($admin);
        $mostra = $this->mostra();
        $antic = $this->disponible();
        $nou = $this->disponible();
        app(QrPreimpressioService::class)->assignar($antic->token, $mostra);
        $tecnic = $this->usuari('tecnic');
        $this->actingAs($tecnic);

        try {
            app(QrSubstitucioService::class)->substituir($mostra, $nou->token, 'Etiqueta deteriorada');
            $this->fail('Només un administrador pot substituir el QR.');
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            $this->assertTrue(true);
        }
        $this->get(route('mostres.qr.substituir', $mostra))->assertForbidden();
        $this->assertDatabaseHas('qr_etiquetes', ['id' => $antic->id, 'estat' => 'assignat']);
    }

    public function test_exigeix_motiu_i_confirmacio(): void
    {
        $admin = $this->usuari();
        $this->actingAs($admin);
        $mostra = $this->mostra();
        $antic = $this->disponible();
        $nou = $this->disponible();
        app(QrPreimpressioService::class)->assignar($antic->token, $mostra);
        Livewire::actingAs($admin)->test(SubstituirQr::class, ['mostraId' => $mostra->id])
            ->set('qrNou', $nou->token)
            ->set('motiu', 'Breu')
            ->set('confirmat', false)
            ->call('confirmar')
            ->assertHasErrors(['motiu', 'confirmat']);
        $this->assertSame(0, QrSubstitucio::count());
    }

    public function test_qr_legacy_es_revoca_quan_s_assigna_un_nou_qr(): void
    {
        $this->actingAs($this->usuari());
        $mostra = $this->mostra('LAB-V06');
        $tokenAntic = app(MostraQrService::class)->token($mostra);
        $nou = $this->disponible();

        $canvi = app(QrSubstitucioService::class)->substituir($mostra, $nou->token, 'Etiqueta antiga deteriorada');
        $this->assertNull($mostra->fresh()->qr_token);
        $this->assertNull($canvi->etiqueta_antiga_id);
        $this->assertSame(hash('sha256', strtolower($tokenAntic)), $canvi->token_legacy_hash);
        $this->get(route('mostres.qr.resolve', $tokenAntic))->assertStatus(410);
        $this->get(route('mostres.qr.resolve', $nou->token))->assertRedirect(route('mostres.proces', $mostra));
    }

    public function test_pot_substituir_dues_vegades_sense_perdre_historial(): void
    {
        $this->actingAs($this->usuari());
        $mostra = $this->mostra();
        $a = $this->disponible();
        $b = $this->disponible();
        $c = $this->disponible();
        app(QrPreimpressioService::class)->assignar($a->token, $mostra);
        $servei = app(QrSubstitucioService::class);
        $servei->substituir($mostra, $b->token, 'Etiqueta deteriorada');
        $servei->substituir($mostra, $c->token, 'Etiqueta nova extraviada');

        $this->assertSame(2, QrSubstitucio::where('mostra_id', $mostra->id)->count());
        $this->assertSame(1, QrEtiqueta::where('mostra_id', $mostra->id)->where('estat', 'assignat')->count());
        $this->get(route('mostres.qr.resolve', $a->token))->assertStatus(410);
        $this->get(route('mostres.qr.resolve', $b->token))->assertStatus(410);
        $this->get(route('mostres.qr.resolve', $c->token))->assertRedirect(route('mostres.proces', $mostra));
    }

    public function test_no_permet_substituir_una_mostra_arxivada(): void
    {
        $this->actingAs($this->usuari());
        $mostra = $this->mostra();
        $old = $this->disponible();
        $new = $this->disponible();
        app(QrPreimpressioService::class)->assignar($old->token, $mostra);
        $mostra->forceFill(['estat' => 'arxivada'])->save();

        try {
            app(QrSubstitucioService::class)->substituir($mostra, $new->token, 'Etiqueta deteriorada');
            $this->fail('No s’hauria de poder substituir una mostra arxivada.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('qrNou', $e->errors());
        }
        $this->assertDatabaseHas('qr_etiquetes', ['id' => $old->id, 'estat' => 'assignat']);
    }
}
