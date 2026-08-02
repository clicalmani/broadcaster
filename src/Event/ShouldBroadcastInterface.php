<?php
namespace Broadcaster\Event;

interface ShouldBroadcastInterface
{
    /**
     * The channels (topics) on which to broadcast the event.
     * @return string[]
     */
    public function broadcastOn(): array;
}