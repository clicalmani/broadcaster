<?php
namespace Broadcaster\Drivers;

use Broadcaster\Event\ShouldBroadcastInterface;
use Psr\Log\LoggerInterface;

class LogBroadcaster implements BroadcasterInterface
{
    public function __construct(private $logger) {}

    public function broadcast(ShouldBroadcastInterface $event): void
    {
        $this->logger->info('🛜 [Broadcast] Événement diffusé sur les canaux', [
            'event' => (new \ReflectionClass($event))->getShortName(),
            'channels' => $event->broadcastOn(),
        ]);
    }
}