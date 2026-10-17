<?php
namespace Broadcaster\Channels;

use Clicalmani\Routing\Builder;
use Clicalmani\Routing\BuilderInterface;
use Clicalmani\Routing\Registry;
use Clicalmani\Routing\Parameter;
use Clicalmani\Routing\Route;
use Clicalmani\Routing\Segment;
use Clicalmani\Core\Support\Facades\Config;

/**
 * ChannelBuilder Class
 * 
 * Builds channel routes: pattern matching on dot-separated topics.
 * 
 * Example patterns:
 *   - orders.{order}       → matches orders.42
 *   - users.{user}.posts   → matches users.7.posts
 * 
 * @package clicalmani/broadcasting
 * @author @clicalmani
 */
class ChannelBuilder extends Builder implements BuilderInterface
{
    /**
     * Parameter marker pattern (Laravel-style).
     */
    private const PARAM_PATTERN = '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/';

    /**
     * Internal Tonka parameter prefix (default ":").
     */
    private string $internalPrefix;

    /**
     * Segment separator (default ".").
     */
    private string $separator;

    public function __construct()
    {
        $this->internalPrefix = Config::channels('parameter_prefix') ?: ':';
        $this->separator      = Config::channels('separator') ?: '.';
    }

    /**
     * Convert a Laravel-style channel pattern to a Tonka-style URI.
     * 
     * "orders.{order}" → "orders.:order"
     * 
     * @param string $pattern
     * @return string
     */
    private function convertToTonka(string $pattern) : string
    {
        return preg_replace_callback(self::PARAM_PATTERN, function ($matches) {
            return $this->internalPrefix . $matches[1];
        }, $pattern);
    }

    /**
     * Create a Route from a channel pattern.
     * 
     * @param string $pattern
     * @return Route
     */
    public function create(string $pattern, bool $withPrefix = false) : Route
    {
        if (false !== $withPrefix) {
            $pattern = Config::broadcasting('auth_prefix') . '.' . $pattern;
        }

        $tonkaPattern = $this->convertToTonka($pattern);
        
        $route = new Route;
        $route->verb = ChannelRegistry::verb();
        $route->setUri($tonkaPattern);

        foreach (explode($this->separator, $tonkaPattern) as $part) {
            $segment = new Segment;
            $segment->name = $part;
            $route[] = $segment;
        }
        
        return $route;
    }

    /**
     * Match a channel string against registered channel routes.
     * 
     * @param string $channel e.g. "orders.42"
     * @return Route[]
     */
    public function matches(string $channel) : array
    {
        $verb     = ChannelRegistry::verb();
        $client   = $this->create($channel, true);
        $segments = count($client);
        
        $candidates = [];
        
        /** @var Route $route */
        foreach (Registry::getRoutesByVerb($verb) as $route) {
            if (count($route) !== $segments) continue;
            if ($route->isCustom()) continue;

            $candidates[] = $route;
        }
        
        return $candidates;
    }

    /**
     * Locate the first matching channel route for a given channel string.
     * 
     * @param string $channel e.g. "orders.42"
     * @return ?Route
     */
    public function locate(string $channel) : ?Route
    {
        $candidates = $this->matches($channel);
        
        if (!$candidates) return null;
        
        $client     = $this->create($channel, true);
        $parameters = $this->parameters($candidates, $client);
        
        foreach ($candidates as $route) {

            if (!$parameters && $route->getParameters()) continue;

            foreach ($parameters as $parameter) {
                if (isset($route[$parameter->position])) {

                    /** @var Segment $segment */
                    $segment = $route[$parameter->position];
                    $segment->value    = $parameter->value;
                    $parameter->name   = $segment->getName();

                    if (false === $segment->isValid()) continue 2;
                }
            }

            if ($client->equals($route)) {
                foreach ($parameters as $parameter) {
                    if (isset($route[$parameter->position])) {
                        /** @var Segment $segment */
                        $segment = $route[$parameter->position];
                        $segment->register();
                    }
                }

                return $route;
            }
        }

        return null;
    }

    /**
     * Retrieve channel parameters from candidate routes.
     * 
     * @param Route[] $candidates
     * @param Route $client
     * @return Parameter[]
     */
    public function parameters(array $candidates, Route $client) : array
    {
        $names = [];

        foreach ($candidates as $match) {
            $names[] = $match->getSegmentsNames();
        }

        $ret = [];

        foreach (array_diff($client->getSegmentsNames(), ...$names) as $key => $value) {
            $param          = new Parameter;
            $param->value   = $value;
            $param->position = $key;
            $ret[]          = $param;
        }

        return $ret;
    }

    /**
     * @override
     * 
     * Not used for channels (no HTTP request context), kept for interface compliance.
     */
    public function getRoute() : ?Route
    {
        return null;
    }
}