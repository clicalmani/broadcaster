<?php
namespace Broadcaster\Test\Stubs;

use Broadcaster\Event\ShouldBroadcastInterface;

/**
 * Nom de diffusion personnalisé via broadcastAs().
 */
class AliasedEvent implements ShouldBroadcastInterface
{
    public function broadcastOn(): array
    {
        return ['orders.42'];
    }

    public function broadcastAs(): string
    {
        return 'order.shipped';
    }
}
