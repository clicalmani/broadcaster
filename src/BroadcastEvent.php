<?php
namespace Broadcaster;

/**
 * BroadcastEvent Class
 * 
 * A generic event used to broadcast arbitrary payloads on arbitrary channels
 * without declaring a dedicated event class.
 * 
 * @package clicalmani/broadcasting
 * @author @clicalmani
 */
class BroadcastEvent implements Event\ShouldBroadcastInterface
{
    public function __construct(
        private readonly string $channel,
        private readonly string $event,
        private readonly array $payload = [],
    ) {}

    public function broadcastOn(): array
    {
        return [$this->channel];
    }

    public function broadcastAs(): string
    {
        return $this->event;
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}