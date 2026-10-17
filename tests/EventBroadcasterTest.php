<?php
namespace Broadcaster\Test;

use Broadcaster\Event\ShouldBroadcastInterface;
use Broadcaster\EventBroadcaster;
use Broadcaster\Test\BroadcasterTestCase;
use Broadcaster\Test\Fakes\FakeHub;
use Broadcaster\Test\Stubs\AfterCommitEvent;
use Broadcaster\Test\Stubs\AliasedEvent;
use Broadcaster\Test\Stubs\ConditionalEvent;
use Broadcaster\Test\Stubs\CustomPayloadEvent;
use Broadcaster\Test\Stubs\MultiChannelEvent;
use Broadcaster\Test\Stubs\OrderShipped;
use Symfony\Component\Mercure\Exception\RuntimeException as MercureRuntimeException;

/**
 * Comportement attendu d'EventBroadcaster :
 * un publish() par canal, topic "https://tonka.framework/channels/{channel}",
 * payload JSON {"event": ..., "data": ...}.
 */
class EventBroadcasterTest extends BroadcasterTestCase
{
    private FakeHub $hub;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hub = new FakeHub;
    }

    public function test_broadcaste_le_nom_court_et_les_proprietes_publiques(): void
    {
        $broadcaster = new EventBroadcaster($this->hub);
        $broadcaster->broadcast(new OrderShipped(status: 'delayed'));

        $this->assertCount(1, $this->hub->updates);

        $update = $this->hub->updates[0];
        $this->assertSame(['https://tonka.framework/channels/orders.42'], $update->getTopics());
        $this->assertSame(
            ['event' => 'OrderShipped', 'data' => ['status' => 'delayed']],
            json_decode($update->getData(), true)
        );
    }

    public function test_broadcastAs_personnalise_le_nom_de_l_evenement(): void
    {
        $broadcaster = new EventBroadcaster($this->hub);
        $broadcaster->broadcast(new AliasedEvent);

        $payload = json_decode($this->hub->updates[0]->getData(), true);
        $this->assertSame('order.shipped', $payload['event']);
    }

    public function test_broadcastWith_personnalise_les_donnees(): void
    {
        $broadcaster = new EventBroadcaster($this->hub);
        $broadcaster->broadcast(new CustomPayloadEvent);

        $payload = json_decode($this->hub->updates[0]->getData(), true);
        $this->assertSame(['reference' => 'CMD-1'], $payload['data']);
    }

    public function test_broadcastWhen_faux_n_publie_rien(): void
    {
        $broadcaster = new EventBroadcaster($this->hub);
        $broadcaster->broadcast(new ConditionalEvent(condition: false));

        $this->assertCount(0, $this->hub->updates);
    }

    public function test_broadcastWhen_vrai_publie(): void
    {
        $broadcaster = new EventBroadcaster($this->hub);
        $broadcaster->broadcast(new ConditionalEvent(condition: true));

        $this->assertCount(1, $this->hub->updates);
    }

    public function test_publie_un_update_par_canal(): void
    {
        $broadcaster = new EventBroadcaster($this->hub);
        $broadcaster->broadcast(new MultiChannelEvent);

        $this->assertCount(2, $this->hub->updates);
        $this->assertSame(
            [
                'https://tonka.framework/channels/orders.42',
                'https://tonka.framework/channels/dashboard',
            ],
            array_map(fn($update) => $update->getTopics()[0], $this->hub->updates)
        );
    }

    /**
     * La diffusion après commit est différée via DB::deadlock() :
     * rien n'est publié immédiatement ; la publication part à l'exécution
     * du callback différé (simulée ici par le double de service "database"),
     * avec attempts()/sleep() de l'événement.
     */
    public function test_evenement_after_commit_n_est_pas_publie_immediatement(): void
    {
        $fakeDb = $this->fakeDatabase();

        $broadcaster = new EventBroadcaster($this->hub);
        $broadcaster->broadcast(new AfterCommitEvent);

        $this->assertCount(0, $this->hub->updates);
        $this->assertCount(1, $fakeDb->deadlocks);
        $this->assertSame(3, $fakeDb->deadlocks[0]['attempts']);
        $this->assertSame(100, $fakeDb->deadlocks[0]['sleep']);

        $fakeDb->deadlocks[0]['callback']();

        $this->assertCount(1, $this->hub->updates);
        $this->assertSame(['https://tonka.framework/channels/orders.42'], $this->hub->updates[0]->getTopics());
    }

    public function test_erreur_du_hub_propagee(): void
    {
        $this->hub->exception = new MercureRuntimeException('hub down');

        $broadcaster = new EventBroadcaster($this->hub);

        $this->expectException(MercureRuntimeException::class);
        $broadcaster->broadcast(new OrderShipped);
    }
}
