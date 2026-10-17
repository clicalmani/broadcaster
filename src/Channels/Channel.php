<?php
namespace Broadcaster\Channels;

use Clicalmani\Routing\Route;
use Clicalmani\Routing\Segment;
use Clicalmani\Routing\Registry;
use Clicalmani\Routing\SegmentValidator;
use Clicalmani\Core\Support\Facades\Config;

/**
 * Channel Class
 * 
 * Represents a registered channel authorization rule.
 * 
 * @package clicalmani/broadcasting
 * @author @clicalmani
 */
class Channel
{
    /**
     * Authorization callback
     * 
     * @var \Closure
     */
    private \Closure $callback;

    /**
     * Channel TTL (seconds) — null means use global config
     * 
     * @var ?int
     */
    private ?int $ttl = null;

    /**
     * @param \Clicalmani\Routing\Route $route
     * @param \Closure|null $callback
     */
    public function __construct(
        private Route $route,
        ?\Closure $callback = null
    ) {
        $this->callback = $callback ?? fn () => null;
    }

    /**
     * Register a new channel.
     * 
     * @param string $pattern
     * @param callable $callback
     * @return static
     */
    public static function make(string $pattern, callable $callback) : static
    {
        /** @var ChannelBuilder $builder */
        $builder = new ChannelBuilder;
        $route   = $builder->create($pattern, true);

        $route->action = $callback; 
        Registry::addRoute($route);

        return new static($route, \Closure::fromCallable($callback));
    }

    /**
     * Set the TTL for this channel.
     * 
     * @param int $seconds
     * @return self
     */
    public function ttl(int $seconds) : self
    {
        $this->ttl = $seconds;
        $this->route->channel_ttl = $seconds;
        return $this;
    }

    /**
     * Apply a validation rule to a parameter (fluent, mirror of Validator::where).
     * 
     * @param string|array $params
     * @param string $rule
     * @return self
     */
    public function where(string|array $params, string $rule) : self
    {
        $params = (array) $params;

        /** @var Segment $segment */
        foreach ($this->route as $segment) {
            if (in_array($segment->getName(), $params, true)) {
                $segment->setValidator(new SegmentValidator($segment->getName(), $rule));
            }
        }

        return $this;
    }

    /**
     * Numeric parameter validation shortcut.
     */
    public function whereNumber(string|array $params) : self
    {
        return $this->where($params, 'numeric');
    }

    /**
     * Integer parameter validation shortcut.
     */
    public function whereInt(string|array $params) : self
    {
        return $this->where($params, 'int');
    }

    public function getRoute() : Route
    {
        return $this->route;
    }

    public function getCallback() : \Closure
    {
        return $this->callback;
    }

    public function getTtl() : ?int
    {
        return $this->ttl;
    }

    public function __get(string $name)
    {
        return match ($name) {
            'route'    => $this->route,
            'callback' => $this->callback,
            'ttl'      => $this->ttl,
        };
    }
}