<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoomRequest;
use App\Http\Requests\UpdateRoomRequest;
use App\Http\Resources\RoomResource;
use App\Models\Room;
use Illuminate\Support\Facades\Log;

class RoomController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return RoomResource::collection(Room::paginate(15));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRoomRequest $request)
    {
        $room = Room::create($request->validated());

        Log::info('Quarto criado', ['room_id' => $room->id, 'hotel_id' => $room->hotel_id]);

        return new RoomResource($room);
    }

    /**
     * Display the specified resource.
     */
    public function show(Room $room)
    {
        return new RoomResource($room);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRoomRequest $request, Room $room)
    {
        $room->update($request->validated());

        Log::info('Quarto atualizado', ['room_id' => $room->id]);

        return new RoomResource($room);
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Room $room)
    {
        if ($room->reserves()->exists()) {
            return response()->json(
                [
                    'message' => "Quarto não pode ser removido porque possui reservas",
                ],
                409
            );
        }

        $room->delete();

        Log::info('Quarto removido', ['room_id' => $room->id]);

        return response()->noContent();
    }
}
