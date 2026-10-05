<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReserveResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'hotel_id' => $this->hotel_id,
            'room_id' => $this->room_id,
            'check_in' => $this->check_in->toDateString(),
            'check_out' => $this->check_out->toDateString(),
            'total' => $this->total,
            'guests' => $this->whenLoaded('guests', fn() => $this->guests->map(fn($guest) => [
                'name' => $guest->name,
                'last_name' => $guest->last_name,
                'phone' => $guest->phone,
            ])),
            'dailies' => $this->whenLoaded('dailies', fn() => $this->dailies->map(fn($daily) => [
                'date' => $daily->date->toDateString(),
                'value' => $daily->value,
            ])),

            'payments' => $this->whenLoaded('payments', fn() => $this->payments->map(fn($payment) => [
                'method' => $payment->method,
                'value' => $payment->value,
            ])),
        ];
    }
}
