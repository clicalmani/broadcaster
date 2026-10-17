<?php
namespace Broadcaster\Test\Channels;

use Broadcaster\Channels\Channel;
use Broadcaster\Channels\ChannelBuilder;
use Broadcaster\Channels\ChannelRegistry;
use Broadcaster\Test\BroadcasterTestCase;

/**
 * ChannelBuilder : conversion des patterns Laravel-style en routes Tonka,
 * localisation d'un canal parmis les canaux enregistrés.
 */
class ChannelBuilderTest extends BroadcasterTestCase
{
    public function test_create_convertit_le_pattern_en_uri_tonka(): void
    {
        $route = (new ChannelBuilder)->create('orders.{order}');

        // Route::setUri normalise le séparateur '.' en '/'
        $this->assertSame('orders/:order', $route->uri);
        $this->assertSame(ChannelRegistry::verb(), $route->verb);
        $this->assertCount(2, $route);

        $segments = iterator_to_array($route);
        $this->assertSame('orders', $segments[0]->getName());
        $this->assertTrue($segments[1]->isParameter());
        $this->assertSame('order', $segments[1]->getName());
    }

    public function test_create_avec_prefixe_auth(): void
    {
        $route = (new ChannelBuilder)->create('orders.{order}', true);

        $this->assertSame('auth/orders/:order', $route->uri);
    }

    public function test_make_enregistre_le_canal_dans_le_registry(): void
    {
        Channel::make('orders.{order}', fn() => true);

        $this->assertCount(1, ChannelRegistry::all());
        // make() applique le préfixe d'authentification, comme locate()
        $this->assertSame('auth/orders/:order', ChannelRegistry::all()[0]->uri);
    }

    public function test_locate_canal_inconnu_renvoie_null(): void
    {
        Channel::make('orders.{order}', fn() => true);

        $this->assertNull((new ChannelBuilder)->locate('unknown.thing'));
    }

    public function test_where_number_rejette_une_valeur_non_numerique(): void
    {
        Channel::make('orders.{order}', fn() => true)->whereNumber('order');

        $this->assertNull((new ChannelBuilder)->locate('orders.abc'));
    }

    public function test_where_number_accepte_une_valeur_numerique(): void
    {
        Channel::make('orders.{order}', fn() => true)->whereNumber('order');

        $route = (new ChannelBuilder)->locate('orders.42');

        $this->assertNotNull($route);
        $this->assertSame('42', $route->getParameters()[0]->value);
    }

    public function test_make_puis_locate(): void
    {
        Channel::make('orders.{order}', fn() => true);

        $route = (new ChannelBuilder)->locate('orders.42');

        $this->assertNotNull($route);
        $this->assertSame('42', $route->getParameters()[0]->value);
    }

    /**
     * LIMITATION CONNUE (héritée de routing) : le test échoue actuellement.
     * ChannelBuilder::parameters() détecte les paramètres par array_diff de
     * VALEURS : une valeur égale à un nom de segment statique est ignorée
     * et le canal n'est plus localisé.
     */
    public function test_valeur_de_parametre_egale_a_un_segment_statique(): void
    {
        Channel::make('orders.{order}', fn() => true);

        $route = (new ChannelBuilder)->locate('orders.orders');

        $this->assertNotNull($route);
        $this->assertSame('orders', $route->getParameters()[0]->value);
    }
}
