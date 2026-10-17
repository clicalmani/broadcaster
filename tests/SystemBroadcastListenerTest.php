<?php
namespace Broadcaster\Test;

use Broadcaster\BroadcastManager;
use Broadcaster\SystemBroadcastListener;
use Broadcaster\Test\Fakes\ArrayLogger;
use Broadcaster\Test\Stubs\OrderShipped;
use Broadcaster\Test\Stubs\PlainEvent;
use Clicalmani\Core\Providers\ContainerServiceProvider;
use Symfony\Component\DependencyInjection\Definition;

class SystemBroadcastListenerTest extends BroadcasterTestCase
{
    private ArrayLogger $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = container()->get('logger');
        $this->logger->records = [];

        // Diffusion via le driver "log" pour rester hors réseau
        $builder = ContainerServiceProvider::get();
        $builder->setDefinition(BroadcastManager::class, (new Definition(BroadcastManager::class))->setSynthetic(true)->setPublic(true));
        $builder->set(BroadcastManager::class, new BroadcastManager($this->managerConfig()));
    }

    public function test_diffuse_les_evenements_broadcaster(): void
    {
        $listener = new SystemBroadcastListener;
        $listener->__invoke(new OrderShipped);

        $this->assertCount(1, $this->logger->records);
        $this->assertSame('OrderShipped', $this->logger->records[0]['context']['event']);
    }

    public function test_ignore_les_evenements_non_broadcaster(): void
    {
        $listener = new SystemBroadcastListener;
        $listener->__invoke(new PlainEvent);

        $this->assertCount(0, $this->logger->records);
    }

    public function test_le_manager_est_resolu_depuis_le_conteneur(): void
    {
        $this->assertTrue(container()->has(BroadcastManager::class));
        $this->assertInstanceOf(BroadcastManager::class, container()->get(BroadcastManager::class));
    }
}
