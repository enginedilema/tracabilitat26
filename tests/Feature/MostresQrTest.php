<?php

namespace Tests\Feature;

use App\Models\Mostra;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class MostresQrTest extends TestCase
{
    use RefreshDatabase;

    private function mostra(): Mostra
    {
        return Mostra::query()->create([
            'identificacio' => 'LAB-QR-001',
            'tipus_mostra' => 'citologia',
            'data_entrada' => '2026-11-06',
            'hora_entrada' => '11:00',
            'recepcio_confirmada' => true,
            'motiu_sollicitud' => 'DADES_CLINIQUES_NO_QR',
        ]);
    }

    private function login(string $rol = 'tecnic', bool $actiu = true): void
    {
        $this->actingAs(User::factory()->create(['rol' => $rol, 'actiu' => $actiu]));
    }

    public function test_el_lector_demana_login_i_denega_comptes_pendents(): void
    {
        $this->get(route('mostres.qr.lector'))->assertRedirect('/login');
        $this->login('pendent');
        $this->get(route('mostres.qr.lector'))->assertForbidden();
    }

    public function test_usuari_autoritzat_pot_entrar_al_lector(): void
    {
        $this->login();
        $this->get(route('mostres.qr.lector'))->assertOk()->assertSee('Lector de codis QR');
    }

    public function test_crea_etiqueta_amb_token_estable_sense_dades_cliniques(): void
    {
        $mostra = $this->mostra();
        $this->login();

        $this->get(route('mostres.qr.etiqueta', $mostra))
            ->assertOk()
            ->assertSee('LAB-QR-001')
            ->assertDontSee('DADES_CLINIQUES_NO_QR');

        $token = $mostra->fresh()->qr_token;
        $this->assertTrue(Str::isUuid($token));

        $this->get(route('mostres.qr.etiqueta', $mostra))->assertOk();
        $this->assertSame($token, $mostra->fresh()->qr_token);
    }

    public function test_imatge_svg_es_privada_i_conte_un_qr(): void
    {
        $mostra = $this->mostra();
        $this->get(route('mostres.qr.imatge', $mostra))->assertRedirect('/login');
        $this->login();

        $response = $this->get(route('mostres.qr.imatge', $mostra));

        $response
            ->assertOk()
            ->assertSee('<svg', false)
            ->assertDontSee('DADES_CLINIQUES_NO_QR');

        $directives = array_map(
            'trim',
            explode(',', $response->baseResponse->headers->get('Cache-Control', ''))
        );

        $this->assertContains('private', $directives);
        $this->assertContains('no-store', $directives);
        $this->assertContains('max-age=0', $directives);
    }

    public function test_token_valid_obri_el_circuit_si_te_permís(): void
    {
        $mostra = $this->mostra();
        $this->login();
        $this->get(route('mostres.qr.etiqueta', $mostra))->assertOk();
        $this->get(route('mostres.qr.resolve', $mostra->fresh()->qr_token))
            ->assertRedirect(route('mostres.proces', $mostra));
    }

    public function test_el_token_no_substitueix_autoritzacio(): void
    {
        $mostra = $this->mostra();
        $this->login('admin');
        $this->get(route('mostres.qr.etiqueta', $mostra))->assertOk();
        $token = $mostra->fresh()->qr_token;

        auth()->logout();
        $this->get(route('mostres.qr.resolve', $token))->assertRedirect('/login');
        $this->login('pendent');
        $this->get(route('mostres.qr.resolve', $token))->assertForbidden();
    }

    public function test_token_desconegut_no_permet_veure_mostres(): void
    {
        $this->login();
        $this->get(route('mostres.qr.resolve', (string) Str::uuid()))->assertNotFound();
    }
}
