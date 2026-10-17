<?php
namespace Broadcaster\Test\Stubs;

use Broadcaster\Event\ShouldBroadcastInterface;

/**
 * Diffusion multi-canaux.
 */
class MultiChannelEvent implements ShouldBroadcastInterface
{
    public function broadcastOn(): array
    {
        return ['orders.42', 'dashboard'];
    }
}
