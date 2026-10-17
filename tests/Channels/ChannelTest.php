<?php
namespace Broadcaster\Test\Channels;

use Broadcaster\Channels\Channel;
use Broadcaster\Channels\ChannelRegistry;
use Broadcaster\Test\BroadcasterTestCase;
use Clicalmani\Routing\Route;

class ChannelTest extends BroadcasterTestCase
{
    public function test_make_renvoie_une_instance_et_enregistre_la_route(): void
    {
        $channel = Channel::make('orders.{order}', fn() => true);

        $this->assertInstanceOf(Channel::class, $channel);
        $this->assertInstanceOf(Route::class, $channel->getRoute());
        $this->assertSame('auth/orders/:order', $channel->getRoute()->uri);
    }

    public function test_where_est_fluent(): void
    {
        $channel = Channel::make('orders.{order}', fn() => true);

        $this->assertSame($channel, $channel->whereNumber('order'));
        $this->assertSame($channel, $channel->where('order', 'numeric'));
    }

    public function test_registry_match_localise_un_canal(): void
    {
        Channel::make('orders.{order}', fn() => true);

        $channel = ChannelRegistry::match('orders.42');

        $this->assertInstanceOf(Channel::class, $channel);
    }

    public function test_registry_match_inconnu_renvoie_null(): void
    {
        $this->assertNull(ChannelRegistry::match('unknown.1'));
    }

    /**
     * BUG CONSTATÉ : le test échoue actuellement.
     * Channel::$callback est une propriété typée jamais initialisée :
     * getCallback() lève "Typed property must not be accessed before initialization".
     */
    public function test_get_callback_renvoie_le_callback_enregistre(): void
    {
        $callback = fn() => true;
        $channel  = Channel::make('orders.{order}', $callback);

        $this->assertSame($callback, $channel->getCallback());
    }
}
