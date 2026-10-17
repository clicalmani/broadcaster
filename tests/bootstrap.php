<?php

declare(strict_types=1);

use Broadcaster\Test\Fakes\ArrayLogger;
use Broadcaster\Test\Fakes\FakeEventDispatcher;
use Clicalmani\Core\Providers\ContainerServiceProvider;
use Lcobucci\JWT\Configuration as JwtConfiguration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Symfony\Component\DependencyInjection\Definition;

require_once __DIR__ . '/../vendor/autoload.php';

// Helpers du framework (app(), container(), event(), call(), env(), ...) — non chargés par l'autoload PSR-4
require_once dirname(__DIR__, 2) . '/foundation/src/helpers.php';

// Helpers du package (broadcast(), channel_authorizer()) — chargés en production par BroadcastServiceProvider::boot()
require_once __DIR__ . '/../src/helpers.php';

// Instance Application légère (sans providers HTTP ni routage), puis conteneur DI cœur (config, logger, ...)
app();
(new ContainerServiceProvider)->boot();

// Registre des règles de validation (numeric, int, ...) requis par SegmentValidator des canaux
(new \Clicalmani\Core\Providers\ValidationServiceProvider)->boot();

$builder = ContainerServiceProvider::get();

// Logger PSR-3 capturé en mémoire, injecté au driver "log"
$builder->setDefinition('logger', (new Definition(ArrayLogger::class))->setPublic(true));

// Configuration JWT Mercure avec un secret de test (équivalent de JwtServiceProvider, sans APP_KEY/MERCURE_JWT_SECRET)
$builder->setDefinition('mercure.jwt.config', (new Definition(JwtConfiguration::class))->setSynthetic(true)->setPublic(true));
$builder->set('mercure.jwt.config', JwtConfiguration::forSymmetricSigner(
    new Sha256,
    InMemory::plainText(Broadcaster\Test\BroadcasterTestCase::JWT_SECRET)
));

// Dispatcher d'événements factice pour event() (helpers broadcast(...args))
$builder->setDefinition('events.discovery', (new Definition(FakeEventDispatcher::class))->setSynthetic(true)->setPublic(true));
$builder->set('events.discovery', new FakeEventDispatcher);
