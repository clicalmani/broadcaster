<?php
namespace Broadcaster\Test;

use Broadcaster\BroadcastEvent;
use Broadcaster\Test\BroadcasterTestCase;

class BroadcastEventTest extends BroadcasterTestCase
{
    public function test_expose_le_canal_le_nom_et_le_payload(): void
    {
        $event = new BroadcastEvent('orders.42', 'order.shipped', ['reference' => 'CMD-1']);

        $this->assertSame(['orders.42'], $event->broadcastOn());
        $this->assertSame('order.shipped', $event->broadcastAs());
        $this->assertSame(['reference' => 'CMD-1'], $event->broadcastWith());
    }

    public function test_payload_vide_par_defaut(): void
    {
        $event = new BroadcastEvent('dashboard', 'ping');

        $this->assertSame([], $event->broadcastWith());
    }

    public function test_implemente_should_broadcast(): void
    {
        $event = new BroadcastEvent('dashboard', 'ping');

        $this->assertInstanceOf(\Broadcaster\Event\ShouldBroadcastInterface::class, $event);
    }
}
