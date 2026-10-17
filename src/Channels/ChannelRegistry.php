<?php
namespace Broadcaster\Channels;

use Clicalmani\Routing\Registry;
use Clicalmani\Core\Support\Facades\Config;

/**
 * ChannelRegistry Class
 * 
 * Registry for channels, backed by Registry (via a dedicated verb).
 * 
 * @package clicalmani/broadcasting
 * @author @clicalmani
 */
class ChannelRegistry
{
    /**
     * Get the internal verb used to store channels.
     */
    public static function verb() : string
    {
        return '__channel__';
    }

    /**
     * All registered channel routes.
     * 
     * @return \Clicalmani\Routing\Route[]
     */
    public static function all() : array
    {
        return Registry::getRoutesByVerb(static::verb());
    }

    /**
     * Find the channel whose pattern matches the given string.
     * 
     * @param string $channel e.g. "orders.42"
     * @return ?\Clicalmani\Broadcasting\Channel
     */
    public static function match(string $channel) : ?Channel
    {
        $builder = new ChannelBuilder;

        if (!$route = $builder->locate($channel)) return null;

        return new Channel($route, fn() => null); // callback résolu via Registry plus tard
    }
}