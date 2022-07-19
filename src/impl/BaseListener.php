<?php

namespace Laura\Lib\Queue;

abstract class BaseListener implements SQIListener
{
    public function streamGroupName()
    {
        return str_replace('\\', '_', get_class($this));
    }

    public function processOldItem()
    {
        return true;
    }
}
