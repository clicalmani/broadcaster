<?php
namespace Broadcaster\Test\Stubs;

use Broadcaster\Event\ShouldBroadcastInterface;

/**
 * Événement minimal : nom = nom court de la classe, données = propriétés publiques.
 */
class OrderShipped implements ShouldBroadcastInterface
{
    public function __construct(public string $status = 'shipped') {}

    public function broadcastOn(): array
    {
        return ['orders.42'];
    }
}
