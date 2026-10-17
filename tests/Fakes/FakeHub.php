<?php
namespace Broadcaster\Test\Fakes;

use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Jwt\TokenFactoryInterface;
use Symfony\Component\Mercure\Update;

/**
 * Hub Mercure factice : capture les Update publiés au lieu de les envoyer sur le réseau.
 */
class FakeHub implements HubInterface
{
    /** @var Update[] */
    public array $updates = [];

    public ?\Throwable $exception = null;

    public function publish(Update $update): string
    {
        if ($this->exception) {
            throw $this->exception;
        }

        $this->updates[] = $update;

        return 'published-id';
    }

    public function getPublicUrl(): string
    {
        return 'http://127.0.0.1:1337/.well-known/mercure';
    }

    public function getFactory(): ?TokenFactoryInterface
    {
        return null;
    }
}
