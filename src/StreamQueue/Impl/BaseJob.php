<?php

namespace Laura\Module\Queue\StreamQueue\Impl;


use Laura\Module\Queue\StreamQueue\SQException;
use Laura\Module\Queue\StreamQueue\SQIJob;

abstract class BaseJob implements SQIJob
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

}
