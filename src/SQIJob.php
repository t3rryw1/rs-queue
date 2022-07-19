<?php

namespace Laura\Lib\Queue;

interface SQIJob
{
    public function dispatch($parameters = []);

    /**
     * @return boolean
     */
    public function handle();

    public function shouldQueue();
}
