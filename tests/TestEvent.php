<?php

namespace Laura\Module\Queue\StreamQueue;

use Laura\Lib\Queue\BaseEvent;

class TestEvent extends BaseEvent
{
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }
}
