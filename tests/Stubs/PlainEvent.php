<?php
namespace Broadcaster\Test\Stubs;

/**
 * Événement non diffusable (n'implémente pas ShouldBroadcastInterface).
 */
class PlainEvent
{
    public string $status = 'idle';
}
