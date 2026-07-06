<?php
namespace Broadcaster\Drivers;

use Broadcaster\Event\ShouldBroadcastInterface;

interface BroadcasterInterface
{
    public function broadcast(ShouldBroadcastInterface $event): void;
}