<?php
namespace Laura\Module\Queue\StreamQueue;


use Laura\Module\Queue\StreamQueue\Impl\BaseEvent;

class TestEvent extends BaseEvent
{
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function getValue(){
        return $this->value;
    }

}
