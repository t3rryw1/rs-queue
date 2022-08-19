<?php

namespace Laura\Module\Queue\StreamQueue;

use Laura\Lib\Queue\BaseListener;

class TestStaticListener extends BaseListener
{
    private static $value = 0;

    public static function getValue()
    {
        return self::$value;
    }

    /**
     * @param TestEvent $event
     * @return bool
     */
    public function handle($event)
    {
        self::$value += $event->getValue();

        return true;
    }
}
