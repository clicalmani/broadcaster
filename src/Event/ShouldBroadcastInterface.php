<?php
namespace Broadcaster\Event;

interface ShouldBroadcastInterface
{
    /**
     * The channels (topics) on which to broadcast the event.
     * @return string[]
     */
    public function broadcastOn(): array;

    /**
     * The data to be sent to the client.
     * @return array
     */
    public function broadcastWith(): array;
}