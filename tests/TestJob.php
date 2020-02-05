<?php

namespace Laura\Module\Queue\StreamQueue;


use Laura\Module\Queue\StreamQueue\Impl\BaseJob;

class TestJob extends BaseJob
{


    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * @return boolean
     */
    public function handle()
    {
        $this->value += 1;
        return false;
    }

    public function getPlusOne()
    {
        return $this->value + 1;
    }
}
