<?php
namespace Broadcaster\Test;

use Broadcaster\BroadcastManager;
use Broadcaster\Drivers\BroadcasterInterface;
use Broadcaster\Drivers\LogBroadcaster;
use Broadcaster\Drivers\MercureBroadcaster;
use Broadcaster\Test\BroadcasterTestCase;
use InvalidArgumentException;
use Lcobucci\JWT\Configuration as JwtConfiguration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Parser;

class BroadcastManagerTest extends BroadcasterTestCase
{
    private function manager(): BroadcastManager
    {
        return new BroadcastManager(container()->get('config')->broadcasting());
    }

    private function parse(string $token): \Lcobucci\JWT\Token
    {
        return JwtConfiguration::forSymmetricSigner(
            new Sha256,
            InMemory::plainText(self::JWT_SECRET)
        )->parser()->parse($token);
    }

    public function test_driver_par_defaut_est_mercure(): void
    {
        $this->assertInstanceOf(MercureBroadcaster::class, $this->manager()->driver());
    }

    public function test_driver_log(): void
    {
        $this->assertInstanceOf(LogBroadcaster::class, $this->manager()->driver('log'));
    }

    /**
     * Le driver mercure construit un Hub réel (sans requête réseau à la construction).
     */
    public function test_driver_mercure_explicit(): void
    {
        $this->assertInstanceOf(MercureBroadcaster::class, $this->manager()->driver('mercure'));
    }

    public function test_les_drivers_sont_mis_en_cache(): void
    {
        $manager = $this->manager();

        $this->assertSame($manager->driver('log'), $manager->driver('log'));
    }

    public function test_connexion_inconnue_leve_une_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('not configured');

        $this->manager()->driver('inexistant');
    }

    public function test_driver_non_supporte_leve_une_exception(): void
    {
        container()->get('config')->set('broadcasting.connections.bad', ['driver' => 'redis']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('not supported');

        $this->manager()->driver('bad');
    }

    public function test_driver_null(): void
    {
        $this->assertInstanceOf(BroadcasterInterface::class, $this->manager()->driver('null'));
    }

    public function test_subscribe_genere_un_token_avec_les_topics(): void
    {
        $token = $this->parse($this->manager()->subscribe(['orders.42', 'notifications.7']));

        $this->assertSame(['orders.42', 'notifications.7'], $token->claims()->get('mercure')['subscribe']);
        $this->assertArrayNotHasKey('publish', $token->claims()->get('mercure'));
    }

    public function test_subscribe_respecte_le_ttl(): void
    {
        $token = $this->parse($this->manager()->subscribe(['orders.42'], ttl: 60));

        /** @var \DateTimeImmutable $iat */
        $iat = $token->claims()->get('iat');
        /** @var \DateTimeImmutable $exp */
        $exp = $token->claims()->get('exp');

        $this->assertSame(60, $exp->getTimestamp() - $iat->getTimestamp());
    }

    public function test_subscribe_ajoute_les_claims_supplementaires(): void
    {
        $token = $this->parse($this->manager()->subscribe(['orders.42'], 60, ['uid' => 7]));

        $this->assertSame(7, $token->claims()->get('uid'));
    }

    public function test_publish_genere_un_token_de_publication(): void
    {
        $token = $this->parse($this->manager()->publish(['orders.*'], ttl: 120));

        $this->assertSame(['orders.*'], $token->claims()->get('mercure')['publish']);
        $this->assertArrayNotHasKey('subscribe', $token->claims()->get('mercure'));

        /** @var \DateTimeImmutable $iat */
        $iat = $token->claims()->get('iat');
        /** @var \DateTimeImmutable $exp */
        $exp = $token->claims()->get('exp');

        $this->assertSame(120, $exp->getTimestamp() - $iat->getTimestamp());
    }
}
