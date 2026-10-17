# Tonka Broadcaster Package

![Tonka Logo](https://clicalmani.github.io/tonka/logo-dark.png)

## Overview

The Tonka Broadcaster Package is a simple PHP library to broadcast real-time events using [Symfony Mercure](https://symfony.com/doc/current/mercure.html). It integrates with the [Tonka framework](https://github.com/clicalmani/tonka) event system, supports private channel authorization with Laravel-style patterns, and generates topic-scoped JWT tokens for secure subscriptions.

## Features

- **Multiple drivers**: `mercure` (real-time), `log` (debug), `null` (no-op).
- **Event broadcasting**: broadcast any event implementing `ShouldBroadcastInterface` through the framework event system.
- **Payload control**: customize the broadcast with `broadcastAs()`, `broadcastWith()` and gate it with `broadcastWhen()`.
- **Transactional safety**: defer broadcasting until the database transaction commits with `ShouldDispatchAfterCommitInterface`.
- **Private channels**: authorize subscriptions with patterns like `orders.{order}`.
- **Scoped JWT tokens**: generate `mercure.subscribe` / `mercure.publish` tokens restricted to specific topics.

## Installation

```bash
composer require clicalmani/broadcaster
```

Then register the service provider in your Tonka application:

```php
Broadcaster\BroadcastServiceProvider::class,
```

## Configuration

Publish or create `config/broadcasting.php`:

```php
<?php

return [
    'default' => 'mercure',

    // Prefix prepended to channel authorization patterns
    'auth_prefix' => 'auth',

    // Default JWT lifetime (seconds) for channel subscription tokens
    'mercure.jwt_ttl' => 900,

    'connections' => [
        'mercure' => [
            'driver' => 'mercure',
            'url'    => 'http://127.0.0.1:1337/.well-known/mercure',
            // Use an explicit publisher token...
            'token'  => env('MERCURE_JWT_TOKEN'),
            // ...or a HS256 secret used to generate one (see MERCURE_JWT_SECRET)
            'secret' => env('MERCURE_JWT_SECRET'),
        ],

        'log'  => ['driver' => 'log'],
        'null' => ['driver' => 'null'],
    ],
];
```

If no `token` is provided, a publisher token (allowing `publish: ['*']`) is generated from the `mercure.jwt.config` service, which relies on the `MERCURE_JWT_SECRET` environment variable.

## Usage

### Broadcasting events

Implement `ShouldBroadcastInterface` and dispatch the event through the framework event system — it is broadcast automatically on the default driver:

```php
<?php

use Broadcaster\Event\ShouldBroadcastInterface;

class OrderShipped implements ShouldBroadcastInterface
{
    public function __construct(private string $reference) {}

    public function broadcastOn(): array
    {
        return ["orders.{$this->reference}"];
    }

    // Optional overrides
    public function broadcastAs(): string  { return 'order.shipped'; } // event name (default: class short name)
    public function broadcastWith(): array { return ['ref' => $this->reference]; } // payload (default: public properties)
    public function broadcastWhen(): bool  { return true; } // broadcast only when truthy
}
```

```php
event(new OrderShipped('CMD-1'));
```

Each channel is published as a Mercure topic `https://tonka.framework/channels/{channel}` with the payload:

```json
{"event": "order.shipped", "data": {"ref": "CMD-1"}}
```

### Quick broadcast

Broadcast an arbitrary payload without declaring an event class:

```php
broadcast('orders.CMD-1', 'order.shipped', ['ref' => 'CMD-1']);
```

### Broadcasting after commit

Implement `ShouldDispatchAfterCommitInterface` to defer the broadcast until the current database transaction has been committed:

```php
class OrderShipped implements ShouldBroadcastInterface, ShouldDispatchAfterCommitInterface
{
    public function broadcastOn(): array { /* ... */ }

    public function attempts(): int { return 3; }   // retries on deadlock
    public function sleep(): int { return 100; }    // delay between retries (ms)
}
```

### Private channel authorization

Register authorization rules with Laravel-style patterns, then authorize a user against the requested channels:

```php
use Broadcaster\Channels\Channel;
use Broadcaster\Channels\ChannelAuthorizer;

Channel::make('orders.{order}', function ($user, $order) {
    return $user->ownsOrder($order);
})
    ->whereNumber('order') // validate dynamic parameters
    ->ttl(60);             // per-channel token lifetime (seconds)

$result = channel_authorizer()->authorize($user, ['orders.42', 'dashboard']);
// ['channels' => ['orders.42'], 'ttl' => 60]
```

The returned `ttl` is the lowest granted channel TTL (or `mercure.jwt_ttl` when none is set), to be used when generating the subscription token.

### Subscription and publisher tokens

Generate topic-scoped JWT tokens signed with `connections.mercure.secret`:

```php
// Subscription token: mercure.subscribe claim
broadcast()->subscribe(['orders.42', 'notifications.7'], ttl: 900, extraClaims: ['uid' => $user->id]);

// Publisher token (server-to-server): mercure.publish claim
broadcast()->publish(['orders.*'], ttl: 3600);
```

### Drivers

| Driver    | Description                                            |
|-----------|--------------------------------------------------------|
| `mercure` | Publishes updates to a Mercure hub (real-time).        |
| `log`     | Logs the event name and channels (debugging).          |
| `null`    | Discards events (explicitly disable broadcasting).     |

```php
broadcast()->driver('log')->broadcast($event); // force a specific driver
```

### Console commands

```bash
# Generate an event class in app/Broadcasting
broadcast:event OrderShipped

# Generate a Mercure JWT token
broadcast:generate-token --publish=https://mydomain.com/orders/* --subscribe=https://mydomain.com/users/123
```

## Testing

```bash
composer test
```

The test suite boots a minimal Tonka container (no HTTP layer, no database): the Mercure hub, logger, event dispatcher and database are replaced by test doubles.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).
