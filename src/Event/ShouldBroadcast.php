<?php
namespace Broadcaster\Event;

interface ShouldBroadcast
{
    /**
     * Les canaux (topics) sur lesquels diffuser l'événement
     * @return string[]
     */
    public function broadcastOn(): array;

    /**
     * Les données à envoyer au client
     * @return array
     */
    public function broadcastWith(): array;
}