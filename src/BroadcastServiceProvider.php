<?php
namespace Broadcaster;

use Clicalmani\Broadcast\EventBroadcaster;
use Clicalmani\Task\ServiceProvider\ServiceProviderInterface;
use Clicalmani\Container\Application;
use Symfony\Component\Mercure\HubInterface;

class BroadcastServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app): void
    {
        // Enregistrement du Broadcaster principal
        app()->addService(EventBroadcaster::class, [
            EventBroadcaster::class,
            static function($config) {
                $config->args([
                    app()->dependency('service', HubInterface::class)
                ]);
            }
        ]);
    }

    public function boot(): void
    {
        $dispatcher = app()->get('events');
        $broadcaster = app()->get(EventBroadcaster::class);

        // On s'abonne à TOUS les événements levés par Tonka
        $dispatcher->addListener('*', static function ($event) use ($broadcaster) {
            if ($event instanceof \Clicalmani\Task\Event\ShouldBroadcastInterface) {
                $broadcaster->broadcast($event);
            }
        });
    }
}