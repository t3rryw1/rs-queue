<?php

namespace Laura\Lib\Queue;

abstract class BaseEvent implements SQIEvent
{
    /**
     * @param array $parameters
     * @throws SQException
     */
    final public function dispatch($parameters = [])
    {
        SQManager::getInstance()->dispatch($this, $parameters);
    }

    public function shouldQueue()
    {
        return false;
    }

    public static function streamName()
    {
        return str_replace('\\', '_', static::class);
    }
}
