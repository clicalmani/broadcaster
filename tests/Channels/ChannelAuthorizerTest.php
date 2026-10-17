<?php
namespace Broadcaster\Test\Channels;

use Broadcaster\Channels\Channel;
use Broadcaster\Channels\ChannelAuthorizer;
use Broadcaster\Test\BroadcasterTestCase;

/**
 * ChannelAuthorizer : résout l'autorisation d'un utilisateur sur des canaux.
 */
class ChannelAuthorizerTest extends BroadcasterTestCase
{
    private ChannelAuthorizer $authorizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authorizer = new ChannelAuthorizer;
    }

    public function test_autorise_un_canal_correspondant(): void
    {
        Channel::make('orders.{order}', fn($user, $order) => $order === '42');

        $result = $this->authorizer->authorize((object) ['id' => 7], ['orders.42']);

        $this->assertSame(['orders.42'], $result['channels']);
        $this->assertSame(900, $result['ttl']);
    }

    public function test_refuse_si_le_callback_renvoie_faux(): void
    {
        Channel::make('orders.{order}', fn($user, $order) => false);

        $result = $this->authorizer->authorize((object) ['id' => 7], ['orders.42']);

        $this->assertSame([], $result['channels']);
    }

    public function test_canal_inconnu_non_autorise(): void
    {
        Channel::make('orders.{order}', fn($user, $order) => true);

        $result = $this->authorizer->authorize((object) ['id' => 7], ['unknown.1']);

        $this->assertSame([], $result['channels']);
    }

    public function test_le_callback_recoit_l_utilisateur_et_les_parametres(): void
    {
        $received = null;
        Channel::make('orders.{order}', function ($user, $order) use (&$received) {
            $received = [$user, $order];

            return true;
        });

        $user = (object) ['id' => 7];
        $this->authorizer->authorize($user, ['orders.42']);

        $this->assertSame([$user, '42'], $received);
    }

    public function test_autorise_plusieurs_canaux(): void
    {
        Channel::make('orders.{order}', fn($user, $order) => true);
        Channel::make('dashboard', fn($user) => true);

        $result = $this->authorizer->authorize((object) ['id' => 7], ['orders.42', 'dashboard']);

        $this->assertSame(['orders.42', 'dashboard'], $result['channels']);
    }

    public function test_ttl_par_canal(): void
    {
        Channel::make('orders.{order}', fn($user, $order) => true)->ttl(60);

        $result = $this->authorizer->authorize((object) ['id' => 7], ['orders.42']);

        $this->assertSame(['orders.42'], $result['channels']);
        $this->assertSame(60, $result['ttl']);
    }
}
