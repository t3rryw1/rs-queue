<?php

namespace Laura\Lib\Queue;

interface SQIEvent
{
    public function dispatch($parameters = []);

    /**
     * @return boolean
     */
    public function shouldQueue();

    /**
     * @return string
     */
    public static function streamName();
}
