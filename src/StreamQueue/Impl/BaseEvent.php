<?php

namespace Laura\Module\Queue\StreamQueue\Impl;


use Laura\Module\Queue\StreamQueue\SQException;
use Laura\Module\Queue\StreamQueue\SQIEvent;

abstract class BaseEvent implements SQIEvent
{
    /**
     * @param array $parameters
     * @throws SQException
     */
    public final function dispatch($parameters = [])
    {
        SQManager::getInstance()->dispatch($this, $parameters);
    }

    public function shouldQueue()
    {
        return false;
    }

    public  static function streamName()
    {
        return str_replace('\\', '_', static::class);
    }
}
