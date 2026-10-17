<?php
namespace Broadcaster\Test;

use Broadcaster\BroadcastManager;
use Broadcaster\BroadcastEvent;
use Broadcaster\Channels\ChannelAuthorizer;
use Broadcaster\Test\Fakes\FakeEventDispatcher;
use Clicalmani\Core\Providers\ContainerServiceProvider;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Helpers du package : broadcast(), channel_authorizer().
 */
class HelpersTest extends BroadcasterTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $builder = ContainerServiceProvider::get();
        $builder->setDefinition(BroadcastManager::class, (new Definition(BroadcastManager::class))->setSynthetic(true)->setPublic(true));
        $builder->set(BroadcastManager::class, new BroadcastManager($this->managerConfig()));
    }

    public function test_broadcast_sans_arguments_renvoie_le_manager(): void
    {
        $this->assertInstanceOf(BroadcastManager::class, broadcast());
    }

    public function test_broadcast_avec_arguments_dispatche_un_broadcast_event(): void
    {
        /** @var FakeEventDispatcher $dispatcher */
        $dispatcher = container()->get('events.discovery');

        $message = broadcast('orders.42', 'order.shipped', ['reference' => 'CMD-1']);

        $this->assertInstanceOf(BroadcastEvent::class, $message);
        $this->assertCount(1, $dispatcher->dispatched);
        $this->assertSame($message, $dispatcher->dispatched[0]);
        $this->assertSame(['orders.42'], $message->broadcastOn());
        $this->assertSame('order.shipped', $message->broadcastAs());
        $this->assertSame(['reference' => 'CMD-1'], $message->broadcastWith());
    }

    public function test_channel_authorizer_renvoie_un_authorizer(): void
    {
        $this->assertInstanceOf(ChannelAuthorizer::class, channel_authorizer());
    }
}
