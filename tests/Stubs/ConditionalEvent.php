<?php
namespace Broadcaster\Test\Stubs;

use Broadcaster\Event\ShouldBroadcastInterface;

/**
 * Condition de diffusion via broadcastWhen().
 */
class ConditionalEvent implements ShouldBroadcastInterface
{
    public function __construct(private readonly bool $condition) {}

    public function broadcastOn(): array
    {
        return ['orders.42'];
    }

    public function broadcastWhen(): bool
    {
        return $this->condition;
    }
}
