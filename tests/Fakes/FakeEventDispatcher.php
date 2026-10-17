<?php
namespace Broadcaster\Test\Fakes;

/**
 * Dispatcher factice pour le service "events.discovery" : capture les événements dispatchés.
 */
class FakeEventDispatcher
{
    /** @var object[] */
    public array $dispatched = [];

    public function dispatch(object $event): object
    {
        $this->dispatched[] = $event;

        return $event;
    }
}
