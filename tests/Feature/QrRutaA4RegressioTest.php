<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\QrPreimpressioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class QrRutaA4RegressioTest extends TestCase
{
    use RefreshDatabase;

    public function test_ruta_d_impressio_no_te_colisio_i_imprimeix_el_lot(): void
    {
        $this->assertSame('etiquetes/lots/{lot}/a4', Route::getRoutes()->getByName('etiquetes.a4')?->uri());

        $this->actingAs(User::factory()->create(['rol' => 'admin', 'actiu' => true]));
        $lot = app(QrPreimpressioService::class)->crearLot(2);

        $this->get(route('etiquetes.a4', $lot))
            ->assertOk()
            ->assertSee('Lot #'.$lot->id);
    }

    public function test_la_ruta_a4_rebutja_els_no_administradors(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'tecnic', 'actiu' => true]));

        $this->get(route('etiquetes.a4', ['lot' => 1]))->assertForbidden();
    }

    public function test_la_ruta_antiga_redirigeix_a_la_nova(): void
    {
        $this->actingAs(User::factory()->create(['rol' => 'admin', 'actiu' => true]));

        $this->get('/etiquetes/a4/12')
            ->assertRedirect(route('etiquetes.a4', ['lot' => 12]));
    }
}
