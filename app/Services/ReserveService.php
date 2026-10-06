<?php

namespace App\Services;

use App\Models\Reserve;
use App\Models\Room;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class ReserveService
{
    public function create(array $data): Reserve
    {
        $room = Room::findOrFail($data['room_id']);

        $this->assertDailiesCoverStay($data);

        $total = round(collect($data['dailies'])->sum('value'), 2);

        $reserve = DB::transaction(function () use ($data, $room, $total) {
            $reserve = Reserve::create([
                'hotel_id' => $room->hotel_id,
                'room_id' => $room->id,
                'check_in' => $data['check_in'],
                'check_out' => $data['check_out'],
                'total' => $total,
            ]);

            foreach ($data['guests'] as $guest) {
                $reserve->guests()->create($guest);
            }

            foreach ($data['dailies'] as $daily) {
                $reserve->dailies()->create($daily);
            }

            foreach ($data['payments'] ?? [] as $payment) {
                $reserve->payments()->create($payment);
            }

            return $reserve->load(['guests', 'dailies', 'payments']);
        });

        Log::info('Reserva criada', [
            'reserve_id' => $reserve->id,
            'room_id' => $reserve->room_id,
            'total' => $reserve->total,
        ]);

        return $reserve;
    }

    private function assertDailiesCoverStay(array $data): void
    {
        $end = Carbon::parse($data['check_out']);
        $expected = [];

        for ($day = Carbon::parse($data['check_in']); $day->lt($end); $day = $day->copy()->addDay()) {
            $expected[] = $day->toDateString();
        }

        $given = collect($data['dailies'])->pluck('date')->sort()->values()->all();

        if ($expected !== $given) {
            Log::warning('Reserva recusada: dailies não cobrem a estadia', [
                'room_id' => $data['room_id'],
                'check_in' => $data['check_in'],
                'check_out' => $data['check_out'],
            ]);
            throw ValidationException::withMessages([
                'dailies' => 'As dailies devem cobrir todas as datas de estadia, uma por dia, desde o check-in até a véspera do check-out',
            ]);
        }
    }
}