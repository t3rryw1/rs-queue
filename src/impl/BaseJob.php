<?php

namespace Laura\Lib\Queue;

abstract class BaseJob implements SQIJob
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
}
