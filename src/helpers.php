<?php

use Broadcaster\BroadcastManager;
use Broadcaster\Channels\ChannelAuthorizer;
use Broadcaster\BroadcastEvent;

if (! function_exists('broadcast')) {
    /**
     * Mercure broadcasting
     * 
     * @return BroadcastManager|BroadcastEvent
     */
    function broadcast(mixed ...$args): BroadcastManager|BroadcastEvent {
        if (!count($args)) return container()->get(BroadcastManager::class);

        $message = new BroadcastEvent(...$args);
        event($message);
        return $message;
    }
}

if (! function_exists('channel_authorizer')) {
    /**
     * Channel authorizer
     * 
     * @return ChannelAuthorizer
     */
    function channel_authorizer(): ChannelAuthorizer {
        return new ChannelAuthorizer;
    }
}