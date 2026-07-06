<?php
namespace Broadcaster\Drivers;

use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Broadcaster\Event\ShouldBroadcastInterface;

class MercureBroadcaster implements BroadcasterInterface
{
    public function __construct(
        private HubInterface $hub,
        private array $config
    ) {}

    public function broadcast(ShouldBroadcastInterface $event): void
    {
        try {
            $eventName = (new \ReflectionClass($event))->getShortName();
            $data = method_exists($event, 'broadcastWith') ? $event->broadcastWith() : (array) $event;

            $payload = json_encode(['event' => $eventName, 'data' => $data]);

            foreach ($event->broadcastOn() as $channel) {
                $topic = 'https://tonka.framework/channels/' . $channel;
                $this->hub->publish(new Update($topic, $payload));
            }
        } catch (\Symfony\Component\Mercure\Exception\RuntimeException $e) {
            throw $e;
        }
    }
}