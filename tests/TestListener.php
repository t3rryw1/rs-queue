<?php

namespace Laura\Module\Queue\StreamQueue;

use Laura\Lib\Queue\BaseListener;

class TestListener extends BaseListener
{
    private $value;

    public function getPlusOne()
    {
        return $this->value + 1;
    }

    /**
     * @param TestEvent $event
     * @return bool
     */
    public function handle($event)
    {
        $this->value = $event->getValue();

        return false;
    }
}
