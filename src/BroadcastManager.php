<?php
namespace Broadcaster;

use Broadcaster\Drivers\BroadcasterInterface;
use Broadcaster\Drivers\MercureBroadcaster;
use Broadcaster\Drivers\LogBroadcaster;
use Broadcaster\Event\ShouldBroadcastInterface;
use Clicalmani\Core\Support\Facades\Tonka;
use Symfony\Component\Mercure\Hub;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Lcobucci\JWT\Configuration as JwtConfig;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Plain;
use Clicalmani\Core\Support\Facades\Config;

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

    /**
     * Generate a subscription token for the given topics.
     * 
     * @param string[] $topics List of authorized topics (e.g. ["orders.42", "notifications.7"])
     * @param int $ttl Token lifetime in seconds
     * @param array<string,mixed> $extraClaims Additional custom claims to include
     * @return string
     */
    public function subscribe(
        array $topics = [],
        int $ttl = 900,
        array $extraClaims = []
    ) : string {
        $config = $this->tokenConfig();

        $now = new \DateTimeImmutable();

        $builder = $config->builder()
            ->issuedAt($now)
            ->expiresAt($now->modify("+{$ttl} seconds"))
            ->withClaim('mercure', [
                'subscribe' => $topics,
            ]);

        foreach ($extraClaims as $name => $value) {
            $builder = $builder->withClaim($name, $value);
        }

        return $builder
            ->getToken($config->signer(), $config->signingKey())
            ->toString();
    }

    /**
     * Generate a publish token (for server-to-server publishing).
     * 
     * @param string[] $topics Topics the server is allowed to publish on
     * @param int $ttl
     * @param array<string,mixed> $extraClaims
     * @return string
     */
    public function publish(
        array $topics = [],
        int $ttl = 3600,
        array $extraClaims = []
    ) : string {
        $config = $this->tokenConfig();

        $now = new \DateTimeImmutable();

        $builder = $config->builder()
            ->issuedAt($now)
            ->expiresAt($now->modify("+{$ttl} seconds"))
            ->withClaim('mercure', [
                'publish' => $topics,
            ]);

        foreach ($extraClaims as $name => $value) {
            $builder = $builder->withClaim($name, $value);
        }

        return $builder
            ->getToken($config->signer(), $config->signingKey())
            ->toString();
    }

    private function tokenConfig() : \Lcobucci\JWT\Configuration
    {
        return JwtConfig::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText(Config::broadcasting('connections.mercure.secret', ''))
        );
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
                public function broadcast(ShouldBroadcastInterface $event): void {} 
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