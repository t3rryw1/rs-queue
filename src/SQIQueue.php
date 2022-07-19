<?php

namespace Laura\Lib\Queue;

interface SQIQueue
{
    public function push($name, $object);

    public function read($key, $count = 1, &$lastId = null, $startingId = 0);

    public function groupRead($name, $groupName, &$id, $count = 1, $newMessage = true, $startingId = 0);

    public function ack($name, $groupName, $id);
}
