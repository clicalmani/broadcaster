<?php
namespace Broadcaster\Test\Stubs;

use Broadcaster\Event\ShouldBroadcastInterface;
use Broadcaster\Event\ShouldDispatchAfterCommitInterface;

/**
 * Événement transactionnel : doit être diffusé après le commit de la transaction.
 */
class AfterCommitEvent implements ShouldBroadcastInterface, ShouldDispatchAfterCommitInterface
{
    public function broadcastOn(): array
    {
        return ['orders.42'];
    }

    public function attempts(): int
    {
        return 3;
    }

    public function sleep(): int
    {
        return 100;
    }
}
