<?php
namespace Broadcaster\Drivers;

use Broadcaster\Event\ShouldBroadcastInterface;
use Broadcaster\Event\ShouldDispatchAfterCommitInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class MercureBroadcaster implements BroadcasterInterface
{
    public function __construct(
        private HubInterface $hub,
        private array $config
    ) {}

    public function broadcast(ShouldBroadcastInterface $event): void
    {
        try {
            // 1. Determine the event name (e.g., "OrderShipped")
            $eventName = method_exists($event, 'broadcastAs') 
                ? $event->broadcastAs() 
                : (new \ReflectionClass($event))->getShortName();

            // 2. Prepare the payload data
            $data = method_exists($event, 'broadcastWith') 
                ? $event->broadcastWith() 
                : $this->extractPublicProperties($event);

            $payload = json_encode([
                'event' => $eventName,
                'data'  => $data
            ]);

            // 3. Broadcast to each defined channel
            foreach ($event->broadcastOn() as $channel) {
                // Verify if event logic is present
                if (method_exists($event, 'broadcastWhen') && !$event->broadcastWhen()) continue;

                $broadcast = function() use ($payload, $channel) {
                    // The Mercure topic URL becomes: https://tonka.framework/channels/{channel}
                    $topic = 'https://tonka.framework/channels/' . $channel;
                    $update = new Update($topic, $payload);
                    $this->hub->publish($update);
                };

                // Transactional events should be broadcasted after the transaction is committed
                if ($event instanceof ShouldDispatchAfterCommitInterface) {
                    // Here you would typically register a callback to be executed after the transaction commits.
                    \Clicalmani\Core\Support\Facades\DB::deadlock($broadcast, $event->attempts(), $event->sleep());
                } else $broadcast(); // Broadcast immediatly
            }
        } catch (\Symfony\Component\Mercure\Exception\RuntimeException $e) {
            throw $e;
        }
    }

    private function extractPublicProperties(object $event): array
    {
        $reflect = new \ReflectionClass($event);
        $props = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);
        $data = [];
        
        foreach ($props as $prop) {
            $data[$prop->getName()] = $prop->getValue($event);
        }
        
        return $data;
    }
}