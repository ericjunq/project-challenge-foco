<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportXmlTest extends TestCase
{
    use RefreshDatabase;

    public function test_importa_os_xmls_para_o_banco(): void
    {
        $this->artisan('xml:import')->assertSuccessful();

        $this->assertDatabaseCount('hotels', 3);
        $this->assertDatabaseCount('rooms', 6);
        $this->assertDatabaseCount('reserves', 6);
        $this->assertDatabaseCount('guests', 6);
        $this->assertDatabaseCount('dailies', 18);
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_importar_duas_vezes_nao_duplica_dados(): void
    {
        $this->artisan('xml:import')->assertSuccessful();
        $this->artisan('xml:import')->assertSuccessful();

        $this->assertDatabaseCount('hotels', 3);
        $this->assertDatabaseCount('rooms', 6);
        $this->assertDatabaseCount('reserves', 6);
        $this->assertDatabaseCount('guests', 6);
        $this->assertDatabaseCount('dailies', 18);
        $this->assertDatabaseCount('payments', 1);
    }
}