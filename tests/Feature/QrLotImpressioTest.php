<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\QrPreimpressioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrLotImpressioTest extends TestCase
{
    use RefreshDatabase;

    public function test_lot_existent_imprimeix_deu_qr(): void
    {
        $admin = User::factory()->create(['rol' => 'admin', 'actiu' => true]);
        $this->actingAs($admin);

        $lot = app(QrPreimpressioService::class)->crearLot(10);
        $resposta = $this->get(route('etiquetes.a4', $lot));

        $resposta->assertOk()->assertSee('Lot #'.$lot->id);
        $this->assertSame(10, substr_count($resposta->getContent(), '<svg'));
    }

    public function test_lot_inexistent_retorna_404(): void
    {
        $admin = User::factory()->create(['rol' => 'admin', 'actiu' => true]);
        $this->actingAs($admin);

        $this->get(route('etiquetes.a4', ['lot' => 999999]))->assertNotFound();
    }
}
