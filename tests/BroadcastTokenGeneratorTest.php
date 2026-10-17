<?php
namespace Broadcaster\Test;

use Broadcaster\BroadcastTokenGenerator;
use Broadcaster\Test\BroadcasterTestCase;
use Lcobucci\JWT\Configuration as JwtConfiguration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;

class BroadcastTokenGeneratorTest extends BroadcasterTestCase
{
    /**
     * Token d'abonnement : claim mercure.subscribe, expiration à 1 heure.
     */
    public function test_genere_un_token_d_abonnement_avec_les_topics(): void
    {
        $generator = new BroadcastTokenGenerator(self::JWT_SECRET);
        $token     = JwtConfiguration::forSymmetricSigner(
            new Sha256,
            InMemory::plainText(self::JWT_SECRET)
        )->parser()->parse($generator->generateSubscribeToken(['orders.42']));

        $this->assertSame(['orders.42'], $token->claims()->get('mercure')['subscribe']);

        /** @var \DateTimeImmutable $iat */
        $iat = $token->claims()->get('iat');
        /** @var \DateTimeImmutable $exp */
        $exp = $token->claims()->get('exp');

        $this->assertSame(3600, $exp->getTimestamp() - $iat->getTimestamp());
    }
}
