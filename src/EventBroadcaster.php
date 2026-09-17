<?php
namespace Broadcaster;

use Clicalmani\Core\Support\Facades\DB;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class EventBroadcaster
{
    public function __construct(
        private HubInterface $hub
    ) {}

    public function broadcast(ShouldBroadcastInterface $event): void
    {
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