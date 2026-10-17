<?php
namespace Broadcaster\Test;

use Broadcaster\Drivers\LogBroadcaster;
use Broadcaster\Test\BroadcasterTestCase;
use Broadcaster\Test\Fakes\ArrayLogger;
use Broadcaster\Test\Stubs\MultiChannelEvent;
use Broadcaster\Test\Stubs\OrderShipped;

class LogBroadcasterTest extends BroadcasterTestCase
{
    public function test_journalise_l_evenement_et_ses_canaux(): void
    {
        $logger = new ArrayLogger;
        $driver = new LogBroadcaster($logger);

        $driver->broadcast(new OrderShipped);

        $this->assertCount(1, $logger->records);
        $this->assertSame('info', $logger->records[0]['level']);
        $this->assertSame('OrderShipped', $logger->records[0]['context']['event']);
        $this->assertSame(['orders.42'], $logger->records[0]['context']['channels']);
    }

    public function test_journalise_tous_les_canaux(): void
    {
        $logger = new ArrayLogger;
        $driver = new LogBroadcaster($logger);

        $driver->broadcast(new MultiChannelEvent);

        $this->assertSame(['orders.42', 'dashboard'], $logger->records[0]['context']['channels']);
    }
}
