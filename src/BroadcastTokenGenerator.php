<?php
namespace Broadcaster;

use Lcobucci\JWT\Token\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;

class BroadcastTokenGenerator 
{
    public function __construct(private string $secretKey) {}

    public function generateSubscribeToken(array $topics): string
    {
        $signer = new Sha256();
        $key = InMemory::plainText($this->secretKey);

        return (new Builder())
            ->withClaim('mercure', ['subscribe' => $topics]) // The authorized channels
            ->withGradualTTL(new \DateInterval('PT1H'))      // Expires after 1 hour
            ->getToken($signer, $key)
            ->toString();
    }
}