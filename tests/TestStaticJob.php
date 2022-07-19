<?php

namespace Laura\Module\Queue\StreamQueue;

use Laura\Lib\Queue\BaseJob;

class TestStaticJob extends BaseJob
{
    private static $staticValue = 0;
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
        self::$staticValue += $this->value;
        return true;
    }

    public static function getStaticValue()
    {
        return self::$staticValue;
    }
}
