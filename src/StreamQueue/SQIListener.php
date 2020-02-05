<?php

namespace Laura\Module\Queue\StreamQueue;


interface SQIListener
{
    /**
     * @param SQIEvent $event
     * @return boolean
     */
    public function handle($event);

    /**
     * @return string
     */
    public function streamGroupName();

    /**
     * decide whether to process old event, when worker run in rescue mode
     * @return boolean
     */
    public function processOldItem();

}
