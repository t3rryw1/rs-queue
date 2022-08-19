<?php

namespace Laura\Lib\Queue;

interface SQIListener
{
    /**
     * @param SQIEvent $event
     * @return bool
     */
    public function handle($event);

    /**
     * @return string
     */
    public function streamGroupName();

    /**
     * decide whether to process old event, when worker run in rescue mode
     * @return bool
     */
    public function processOldItem();
}
