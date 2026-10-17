<?php
namespace Broadcaster\Test;

use Clicalmani\Core\Acme\Configure;
use Clicalmani\Routing\Registry;
use PHPUnit\Framework\TestCase;

abstract class BroadcasterTestCase extends TestCase
{
    public const JWT_SECRET = 'test-secret-0123456789abcdef-test-secret';

    protected function setUp(): void
    {
        parent::setUp();

        $this->resetConfigureStorage();
        Registry::reset();

        container()->get('config')->set('broadcasting', [
            'default'       => 'mercure',
            'auth_prefix'   => 'auth',
            'mercure.jwt_ttl' => 900,
            'connections'   => [
                'mercure' => [
                    'driver' => 'mercure',
                    'url'    => 'http://127.0.0.1:1337/.well-known/mercure',
                    'secret' => self::JWT_SECRET,
                ],
                'log'  => ['driver' => 'log'],
                'null' => ['driver' => 'null'],
            ],
        ]);

        // Requis par Clicalmani\Routing\Segment (préfixe de paramètre)
        container()->get('config')->set('route', [
            'default_builder'  => \Clicalmani\Routing\Factory\BasicBuilder::class,
            'parameter_prefix' => ':',
        ]);
    }

    protected function tearDown(): void
    {
        Registry::reset();
        $this->resetConfigureStorage();
        parent::tearDown();
    }

    /**
     * Configuration du driver par défaut pour les tests qui enregistrent
     * BroadcastManager dans le conteneur.
     */
    protected function managerConfig(): array
    {
        return [
            'default'     => 'log',
            'connections' => [
                'log'  => ['driver' => 'log'],
            ],
        ];
    }

    private function resetConfigureStorage(): void
    {
        $storage = new \ReflectionProperty(Configure::class, 'storage');
        $storage->setValue(null, []);
    }

    /**
     * Remplace le service "database" par un double capturant les callbacks
     * différés (diffusion après commit) — la facade DB résout cet id.
     */
    protected function fakeDatabase(): \Broadcaster\Test\Fakes\FakeDatabase
    {
        $fake = new \Broadcaster\Test\Fakes\FakeDatabase;

        $builder = \Clicalmani\Core\Providers\ContainerServiceProvider::get();
        $builder->setDefinition('database', (new \Symfony\Component\DependencyInjection\Definition(\Broadcaster\Test\Fakes\FakeDatabase::class))->setSynthetic(true)->setPublic(true));
        $builder->set('database', $fake);

        return $fake;
    }
}
