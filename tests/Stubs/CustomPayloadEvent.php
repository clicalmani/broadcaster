<?php
namespace Broadcaster\Test\Stubs;

use Broadcaster\Event\ShouldBroadcastInterface;

/**
 * Données personnalisées via broadcastWith().
 */
class CustomPayloadEvent implements ShouldBroadcastInterface
{
    public string $hidden = 'should-not-appear';

    public function broadcastOn(): array
    {
        return ['orders.42'];
    }

    public function broadcastWith(): array
    {
        return ['reference' => 'CMD-1'];
    }
}
