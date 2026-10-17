<?php
namespace Broadcaster\Test\Fakes;

/**
 * Double du service "database" : capture les callbacks différés via deadlock()
 * (mécanisme utilisé par la diffusion après commit) au lieu d'ouvrir une transaction PDO.
 */
class FakeDatabase
{
    /** @var array<int, array{callback: \Closure, attempts: int, sleep: int}> */
    public array $deadlocks = [];

    public function deadlock(\Closure $callback, int $attempts = 5, int $sleep = 100): mixed
    {
        $this->deadlocks[] = [
            'callback' => $callback,
            'attempts' => $attempts,
            'sleep'    => $sleep,
        ];

        return null;
    }
}
