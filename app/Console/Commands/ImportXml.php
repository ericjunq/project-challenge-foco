<?php

namespace App\Console\Commands;

use App\Models\Hotel;
use App\Models\Room;
use App\Models\Reserve;
use Illuminate\Support\Facades\DB;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('xml:import')]
#[Description('Importação hotéis, quartos e reservas dos XMLs em database/xml')]
class ImportXml extends Command
{
    /**
     * Execute the console command.
     */

    public function handle(): int
    {
        $this->importHotels();
        $this->importRooms();
        $this->importReserves();

        return self::SUCCESS;
    }

    private function report(string $message, array $context = []): void
    {
        $this->warn($message);
        Log::channel('import')->warning($message, $context);
    }

    private function importHotels(): void
    {
        $path = database_path('xml/hotels.xml');

        if (!file_exists($path)) {
            $this->error("Arquivo não encontrado: {$path}");
            Log::channel('import')->error("Arquivo não encontrado: {$path}");
            return;
        }

        $xml = simplexml_load_file($path);

        foreach ($xml->Hotel as $item) {
            Hotel::updateOrCreate(
                ['external_id' => (int) $item['id']],
                ['name' => (string) $item->Name]
            );
        }

        $this->info('Hotéis importados com sucesso');
    }

    private function importRooms(): void
    {
        $path = database_path('xml/rooms.xml');

        if (!file_exists($path)) {
            $this->error("Arquivo não encontrado: {$path}");
            Log::channel('import')->error("Arquivo não encontrado: {$path}");
            return;
        }

        $xml = simplexml_load_file($path);

        foreach ($xml->Room as $item) {
            $hotel = Hotel::where('external_id', (int) $item['hotelCode'])->first();

            if (!$hotel) {
                $this->report("Quarto {$item['id']} ignorado: hotel {$item['hotelCode']} não encontrado.");
                continue;
            }

            Room::updateOrCreate(
                ['external_id' => (int) $item['id']],
                [
                    'hotel_id' => $hotel->id,
                    'name' => (string) $item->Name,
                ]
            );
        }

        $this->info('Quartos importados com sucesso');
    }

    private function importReserves(): void
    {
        $path = database_path('xml/reserves.xml');

        if (!file_exists($path)) {
            Log::channel('import')->error("Arquivo não encontrado: {$path}");
            return;
        }

        $xml = simplexml_load_file($path);


        foreach ($xml->Reserve as $item) {
            $externalId = (int) $item['id'];

            $hotel = Hotel::where('external_id', (int) $item['hotelCode'])->first();
            $room = Room::where('external_id', (int) $item['roomCode'])->first();

            if (!$hotel || !$room) {
                $this->report("Reserva {$externalId} ignorada. \nMotivo: hotel ou quarto não encontrados");
                continue;
            }

            if ($room->hotel_id !== $hotel->id) {
                $this->report("Reserva {$externalId} ignorada. \nMotivo: o quarto não pertence ao hotel informado");
                continue;
            }

            $checkIn = (string) $item->CheckIn;
            $checkOut = (string) $item->CheckOut;

            if ($checkOut <= $checkIn) {
                $this->report("Reserva {$externalId} ignorada. \nMotivo: o check-out é igual ou anterior ao check-in");
                continue;
            }

            try {
                DB::transaction(function () use ($item, $externalId, $hotel, $room, $checkIn, $checkOut) {
                    $reserve = Reserve::updateOrCreate(
                        ['external_id' => $externalId],
                        [
                            'hotel_id' => $hotel->id,
                            'room_id' => $room->id,
                            'check_in' => $checkIn,
                            'check_out' => $checkOut,
                            'total' => (float) $item->Total,
                        ]
                    );

                    $reserve->guests()->delete();
                    $reserve->dailies()->delete();
                    $reserve->payments()->delete();

                    foreach ($item->Guests->Guest as $guest) {
                        $reserve->guests()->create([
                            'name' => (string) $guest->Name,
                            'last_name' => (string) $guest->LastName,
                            'phone' => (string) $guest->Phone,
                        ]);
                    }

                    foreach ($item->Dailies->Daily as $daily) {
                        $date = (string) $daily->Date;

                        if ($date < $checkIn || $date >= $checkOut) {
                            $this->report("Reserva {$externalId}: daily {$date} fora do período de estadia");
                        }

                        $reserve->dailies()->create([
                            'date' => $date,
                            'value' => (float) $daily->Value,
                        ]);
                    }

                    if (isset($item->Payments)) {
                        foreach ($item->Payments->Payment as $payment) {
                            $reserve->payments()->create([
                                'method' => (int) $payment->Method,
                                'value' => (float) $payment->Value,
                            ]);
                        }
                    }

                });
            } catch (\Throwable $e) {
                $this->error("Falha na reserva {$externalId}: {$e->getMessage()}");
                Log::channel('import')->error("Falha na reserva {$externalId}", ['erro' => $e->getMessage()]);
            }
        }

        $this->info('Import de reservas concluído com sucesso');
    }
}