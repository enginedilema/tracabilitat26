<?php

namespace Tests\Feature;

use App\Models\Mostra;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class EtiquetesMostresImpressioTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_impressio_de_mostres_te_una_ruta_diferent_dels_lots_qr(): void
    {
        $this->assertSame('etiquetes/a4', Route::getRoutes()->getByName('etiquetes.mostres.a4')?->uri());
        $this->assertSame('etiquetes/lots/{lot}/a4', Route::getRoutes()->getByName('etiquetes.a4')?->uri());
    }

    public function test_usuari_autoritzat_pot_imprimir_dues_copies_duna_mostra(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'admin', 'actiu' => true]));

        $mostra = Mostra::create([
            'identificacio' => 'IMP-MOSTRA-001',
            'tipus_mostra' => 'citologia',
            'data_entrada' => '2026-11-08',
            'hora_entrada' => '10:00',
            'recepcio_confirmada' => true,
        ]);

        $resposta = $this->withSession([
            'etiquetes_a4.items' => [
                ['mostra_id' => $mostra->id, 'copies' => 2],
            ],
        ])->get(route('etiquetes.mostres.a4'));

        $resposta->assertOk()->assertSee('IMP-MOSTRA-001');
        $this->assertSame(2, substr_count($resposta->getContent(), 'aria-label="Etiqueta de la mostra IMP-MOSTRA-001"'));
    }

    public function test_no_es_poden_imprimir_mostres_sense_una_seleccio(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'admin', 'actiu' => true]));

        $this->get(route('etiquetes.mostres.a4'))->assertNotFound();
    }

    public function test_la_impressio_de_mostres_requereix_autenticacio(): void
    {
        $this->get(route('etiquetes.mostres.a4'))->assertRedirect('/login');
    }
}
