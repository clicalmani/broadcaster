<?php
namespace Broadcaster\Test\Fakes;

use Psr\Log\AbstractLogger;
use Stringable;

/**
 * Logger PSR-3 capturant les enregistrements en mémoire.
 *
 * @property array<int, array{level: string, message: string, context: array}> $records
 */
class ArrayLogger extends AbstractLogger
{
    public array $records = [];

    public function log($level, Stringable|string $message, array $context = []): void
    {
        $this->records[] = [
            'level'   => $level,
            'message' => (string) $message,
            'context' => $context,
        ];
    }
}
