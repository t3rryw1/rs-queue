<?php

namespace Laura\Lib\Queue;

interface SQIEvent
{
    public function dispatch($parameters = []);

    /**
     * @return bool
     */
    public function shouldQueue();

    /**
     * @return string
     */
    public static function streamName();
}
