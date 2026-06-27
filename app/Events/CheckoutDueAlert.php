<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CheckoutDueAlert implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $hotelId,
        public readonly array  $rooms,   // array of room summary arrays
    ) {}

    /**
     * Each hotel gets its own private channel so only that hotel's
     * logged-in staff see the alert.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel("hotel.{$this->hotelId}.alerts"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'checkout.due';
    }

    public function broadcastWith(): array
    {
        return [
            'rooms' => $this->rooms,
        ];
    }
}