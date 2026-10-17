<?php
namespace Broadcaster;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;

class BroadcastTokenGenerator 
{
    public function __construct(private string $secretKey) {}

    public function generateSubscribeToken(array $topics): string
    {
        $config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($this->secretKey)
        );

        $now = new \DateTimeImmutable();

        return $config->builder()
            ->issuedAt($now)
            ->expiresAt($now->modify('+1 hour'))
            ->withClaim('mercure', ['subscribe' => $topics]) // The authorized channels
            ->getToken($config->signer(), $config->signingKey())
            ->toString();
    }
}
