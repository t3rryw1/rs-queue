<?php

namespace Laura\Module\Queue\StreamQueue\Impl;


use Laura\Module\Queue\StreamQueue\SQIListener;

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
