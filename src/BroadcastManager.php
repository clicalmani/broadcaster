<?php
namespace Broadcaster;

use Broadcaster\Drivers\BroadcasterInterface;
use Broadcaster\Drivers\MercureBroadcaster;
use Broadcaster\Drivers\LogBroadcaster;
use Clicalmani\Foundation\Support\Facades\Tonka;
use Symfony\Component\Mercure\Hub;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Lcobucci\JWT\Configuration as JwtConfig;

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
            throw new \InvalidArgumentException("The broadcaster [{$name}] is not configured.");
        }

        $connectionConfig = $connections[$name];
        
        return match ($connectionConfig['driver']) {
            'mercure' => new MercureBroadcaster(
                new Hub(
                    $connectionConfig['url'], 
                    new StaticTokenProvider($this->resolveMercureToken($connectionConfig))
                ),
                $connectionConfig
            ),
            'log' => new LogBroadcaster(container()->get('logger')),
            'null' => new class implements BroadcasterInterface { 
                public function broadcast(\Clicalmani\Task\Event\ShouldBroadcastInterface $event): void {} 
            },
            default => throw new \InvalidArgumentException("Driver [{$connectionConfig['driver']}] is not supported.")
        };
    }

    private function resolveMercureToken(array $config): string
    {
        // If a real token is explicitly provided in the .env, use it directly
        if (!empty($config['token'])) {
            return $config['token'];
        }

        /** @var JwtConfig $jwtConfig */
        $jwtConfig = container()->get('mercure.jwt.config');

        $token = $jwtConfig->builder()
                    ->withClaim('mercure', ['publish' => ['*']])
                    ->getToken($jwtConfig->signer(), $jwtConfig->signingKey());
        
        return $token->toString();
    }
}