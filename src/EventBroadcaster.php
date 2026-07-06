<?php
namespace Broadcaster;

use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class EventBroadcaster
{
    public function __construct(
        private HubInterface $hub
    ) {}

    public function broadcast(ShouldBroadcastInterface $event): void
    {
        // 1. Déterminer le nom de l'événement (ex: "OrderShipped")
        $eventName = (new \ReflectionClass($event))->getShortName();

        // 2. Préparer les données (payload)
        $data = method_exists($event, 'broadcastWith') 
            ? $event->broadcastWith() 
            : $this->extractPublicProperties($event);

        $payload = json_encode([
            'event' => $eventName,
            'data'  => $data
        ]);

        // 3. Diffuser sur chaque canal défini
        foreach ($event->broadcastOn() as $channel) {
            // L'URL de topic Mercure devient : https://tonka.framework/channels/{channel}
            $topic = 'https://tonka.framework/channels/' . $channel;
            
            $update = new Update($topic, $payload);
            
            $this->hub->publish($update);
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