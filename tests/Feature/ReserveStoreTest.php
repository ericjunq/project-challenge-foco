<?php

namespace Tests\Feature;

use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReserveStoreTest extends TestCase
{

    use RefreshDatabase;

    private function payload(Room $room, array $overrides = []): array
    {

        return array_merge([
            'room_id' => $room->id,
            'check_in' => '2026-12-01',
            'check_out' => '2026-12-04',
            'guests' => [
                ['name' => 'Eric', 'last_name' => 'Junqueira', 'phone' => '11999999999'],
            ],
            'dailies' => [
                ['date' => '2026-12-01', 'value' => 100],
                ['date' => '2026-12-02', 'value' => 100],
                ['date' => '2026-12-03', 'value' => 100],
            ],
            'payments' => [
                ['method' => 1, 'value' => 100],
            ],
        ], $overrides);
    }

    public function test_cria_reserva_com_filhas_e_total_calculado(): void
    {
        $room = Room::factory()->create();

        $response = $this->postJson('/api/reserves', $this->payload($room));

        $response->assertCreated()
            ->assertJsonPath('data.total', '300.00');

        $this->assertDatabaseHas('reserves', [
            'room_id' => $room->id,
            'hotel_id' => $room->hotel_id,
        ]);

        $this->assertDatabaseCount('guests', 1);
        $this->assertDatabaseCount('dailies', 3);
        $this->assertDatabaseCount('payments', 1);
    }

    public function test_rejeição_de_dailies_que_nao_cobrem_estadia(): void
    {
        $room = Room::factory()->create();

        $response = $this->postJson('api/reserves', $this->payload($room, [
            'dailies' => [
                ['date' => '2026-12-01', 'value' => 100],
                ['date' => '2026-12-02', 'value' => 100],
            ],
        ]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['dailies']);

        $this->assertDatabaseCount('reserves', 0);
    }

    public function test_rejeita_dailies_com_data_repetida(): void
    {
        $room = Room::factory()->create();

        $response = $this->postJson('/api/reserves', $this->payload($room, [
            'dailies' => [
                ['date' => '2026-12-01', 'value' => 100],
                ['date' => '2026-12-01', 'value' => 100],
                ['date' => '2026-12-03', 'value' => 100],
            ],
        ]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['dailies']);

        $this->assertDatabaseCount('reserves', 0);
    }

    public function test_rejeita_checkout_anterior_ao_checkin(): void
    {
        $room = Room::factory()->create();

        $response = $this->postJson('/api/reserves', $this->payload($room, [
            'check_out' => '2026-11-30',
        ]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['check_out']);

        $this->assertDatabaseCount('reserves', 0);
    }

    public function test_rejeita_hospede_sem_telefone(): void
    {
        $room = Room::factory()->create();

        $response = $this->postJson('/api/reserves', $this->payload($room, [
            'guests' => [
                ['name' => 'Maria', 'last_name' => 'Silva'],
            ],
        ]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['guests.0.phone']);

        $this->assertDatabaseCount('reserves', 0);
        $this->assertDatabaseCount('guests', 0);
    }

    public function test_rejeita_reserva_sem_hospedes(): void
    {
        $room = Room::factory()->create();

        $response = $this->postJson('/api/reserves', $this->payload($room, [
            'guests' => [],
        ]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['guests']);

        $this->assertDatabaseCount('reserves', 0);
    }

    public function test_rejeita_quarto_inexistente(): void
    {
        $room = Room::factory()->create();

        $response = $this->postJson('/api/reserves', $this->payload($room, [
            'room_id' => 999999,
        ]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['room_id']);

        $this->assertDatabaseCount('reserves', 0);
    }

    public function test_pagamentos_sao_opcionais(): void
    {
        $room = Room::factory()->create();

        $data = $this->payload($room);
        unset($data['payments']);

        $response = $this->postJson('/api/reserves', $data);

        $response->assertCreated();

        $this->assertDatabaseCount('reserves', 1);
        $this->assertDatabaseCount('payments', 0);
    }
}