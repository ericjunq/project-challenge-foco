<?php

namespace Tests\Feature;

use App\Models\Hotel;
use App\Models\Reserve;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_listar_quartos(): void
    {
        Room::factory()->count(3)->create();

        $this->getJson('api/rooms')
            ->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_mostra_um_quarto(): void
    {
        $room = Room::factory()->create();

        $this->getJson("api/rooms/{$room->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $room->id)
            ->assertJsonPath('data.name', $room->name);
    }

    public function test_retorno_404_para_quarto_inexistente(): void
    {
        $this->getJson('api/rooms/999999')->assertNotFound();
    }

    public function test_criacao_quarto(): void
    {
        $hotel = Hotel::factory()->create();

        $this->postJson('api/rooms', ['hotel_id' => $hotel->id, 'name' => 'Suíte Master'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Suíte Master');

        $this->assertDatabaseHas('rooms', ['hotel_id' => $hotel->id, 'name' => 'Suíte Master']);
    }

    public function test_rejeicao_quarto_com_hotel_inexistente(): void
    {
        $this->postJson('api/rooms', ['hotel_id' => 99999, 'name' => 'Suíte Master'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['hotel_id']);

        $this->assertDatabaseCount('rooms', 0);
    }

    public function test_rejeicao_quarto_sem_campo_obrigatório(): void
    {
        $this->postJson('api/rooms', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['hotel_id', 'name']);
    }

    public function test_atualizar_nome_quarto(): void
    {
        $room = Room::factory()->create();

        $this->putJson("api/rooms/{$room->id}", ['name' => 'Suíte Presidencial'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Suíte Presidencial');

        $this->assertDatabaseHas('rooms', ['id' => $room->id, 'name' => 'Suíte Presidencial']);
    }

    public function test_remover_quarto_sem_reservas(): void
    {
        $room = Room::factory()->create();

        $this->deleteJson("api/rooms/{$room->id}")->assertNoContent();

        $this->assertDatabaseMissing('rooms', ['id' => $room->id]);
    }

    public function test_remover_quarto_com_reserva(): void
    {
        $room = Room::factory()->create();

        Reserve::create([
            'hotel_id' => $room->hotel_id,
            'room_id' => $room->id,
            'check_in' => '2026-12-01',
            'check_out' => '2026-12-02',
            'total' => 100,
        ]);

        $this->deleteJson("api/rooms/{$room->id}")
            ->assertStatus(409);

        $this->assertDatabaseHas('rooms', ['id' => $room->id]);
        $this->assertDatabaseCount('reserves', 1);
    }
}