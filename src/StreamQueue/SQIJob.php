<?php

namespace Laura\Module\Queue\StreamQueue;


interface SQIJob
{
    public function dispatch($parameters = []);

    /**
     * @return boolean
     */
    public function handle();

    public function shouldQueue();

}
