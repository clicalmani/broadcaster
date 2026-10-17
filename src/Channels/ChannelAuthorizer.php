<?php
namespace Broadcaster\Channels;

use Clicalmani\Routing\Registry;
use Clicalmani\Core\Support\Facades\Config;

/**
 * ChannelAuthorizer Class
 * 
 * Resolves whether a user is authorized on a given Mercure topic.
 * 
 * @package clicalmani/broadcasting
 * @author @clicalmani
 */
class ChannelAuthorizer
{
    /**
     * Authorize a user against a list of requested channels.
     * 
     * @param mixed $user
     * @param string[] $channels
     * @return array{channels: string[], ttl: int}
     */
    public function authorize(mixed $user, array $channels) : array
    {
        $granted = [];
        $ttl     = null;

        foreach ($channels as $channel) {
            $builder = new ChannelBuilder;
            $route   = $builder->locate($channel);
            
            if (!$route) continue;

            $params = $this->extractParameters($route);

            if (!$this->call($route, $user, $params)) continue;

            $granted[] = $channel;

            $routeTtl = $route->channel_ttl ?? Config::broadcasting('connections.mercure.jwt_ttl', 900);
            $ttl      = $ttl === null ? $routeTtl : min($ttl, $routeTtl);
        }

        return [
            'channels' => $granted,
            'ttl'      => $ttl ?? Config::broadcasting('mercure.jwt_ttl', 900),
        ];
    }

    /**
     * @param \Clicalmani\Routing\Route $route
     * @return array<string,mixed>
     */
    private function extractParameters($route) : array
    {
        $params = [];

        /** @var \Clicalmani\Routing\Segment $segment */
        foreach ($route as $segment) {
            if ($segment->isParameter()) {
                $params[$segment->getName()] = $segment->value;
            }
        }

        return $params;
    }

    /**
     * Invoke the channel callback with the user and the dynamic parameters.
     */
    private function call($route, mixed $user, array $params) : bool
    {
        $arguments = [$user];

        /** @var \Clicalmani\Routing\Segment $segment */
        foreach ($route as $segment) {
            if ($segment->isParameter()) {
                $arguments[] = $params[$segment->getName()] ?? null;
            }
        }

        return (bool) call($route->action, ...$arguments);
    }
}