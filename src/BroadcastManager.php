<?php
namespace Broadcaster;

use Broadcaster\Drivers\BroadcasterInterface;
use Broadcaster\Drivers\MercureBroadcaster;
use Broadcaster\Drivers\LogBroadcaster;
use Symfony\Component\Mercure\Hub;
use Symfony\Component\Mercure\Jwt\StaticJwtProvider;

class BroadcastManager
{
    private array $drivers = [];
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function driver(?string $name = null): BroadcasterInterface
    {
        $name = $name ?: $this->config['default'];

        if (!isset($this->drivers[$name])) {
            $this->drivers[$name] = $this->createDriver($name);
        }

        return $this->drivers[$name];
    }

    private function createDriver(string $name): BroadcasterInterface
    {
        $connections = $this->config['connections'] ?? [];
        
        if (!isset($connections[$name])) {
            throw new \InvalidArgumentException("Le broadcaster [{$name}] n'est pas configuré.");
        }

        $connectionConfig = $connections[$name];

        return match ($connectionConfig['driver']) {
            'mercure' => new MercureBroadcaster(
                new Hub($connectionConfig['url'], new StaticJwtProvider($connectionConfig['token'])),
                $connectionConfig
            ),
            'log' => new LogBroadcaster(container()->get('logger')), // Ajuste selon le nom de ton service de log
            'null' => new class implements BroadcasterInterface { 
                public function broadcast(\Clicalmani\Task\Event\ShouldBroadcastInterface $event): void {} 
            },
            default => throw new \InvalidArgumentException("Driver [{$connectionConfig['driver']}] non supporté.")
        };
    }
}