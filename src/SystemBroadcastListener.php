<?php
namespace Broadcaster;

class SystemBroadcastListener
{
    public function __invoke(object $event): void
    {
        if ($event instanceof Event\ShouldBroadcastInterface && container()->has(BroadcastManager::class)) {
            $manager = container()->get(BroadcastManager::class);
            $manager->driver()->broadcast($event);
        }
    }
}