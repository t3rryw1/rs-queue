<?php

namespace Laura\Lib\Queue;

interface SQIJob
{
    public function dispatch($parameters = []);

    /**
     * @return bool
     */
    public function handle();

    public function shouldQueue();
}
