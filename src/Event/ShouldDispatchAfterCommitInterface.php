<?php

namespace Broadcaster\Event;

/**
 * © TONKA Framework
 * 
 * This interface is used to mark events that should be broadcasted after the database transaction has been committed.
 * 
 * @author clicalmani
 */
interface ShouldDispatchAfterCommitInterface
{
    /**
     * Get the number of retry attempts for broadcasting the event after commit.
     * 
     * @return int
     */
    public function attemps(): int;

    /**
     * Get the delay (in milliseconds) between retry attempts.
     * 
     * @return int
     */
    public function sleep(): int;
}