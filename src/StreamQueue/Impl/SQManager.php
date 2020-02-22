<?php


namespace Laura\Module\Queue\StreamQueue\Impl;


use Exception;
use Laura\Lib\Base\Log;
use Laura\Module\Queue\StreamQueue\SQException;
use Laura\Module\Queue\StreamQueue\SQIEvent;
use Laura\Module\Queue\StreamQueue\SQIJob;
use Laura\Module\Queue\StreamQueue\SQIListener;
use Laura\Module\Queue\StreamQueue\SQIQueue;

class SQManager
{
    const SQ_MANAGER_PREFIX = "sqmanager:";
    const SQ_MANAGER_JOB_STREAM = "jobstream";
    const SQ_MANAGER_JOB_HANDLER = "jobhandler";

    /**
     * @var SQIQueue $queue
     */
    private $queue;

    /**
     * @var SQManager
     */
    private static $instance;

    private $eventTable = [];

    private $errorHandler = null;


    public function loadQueueConfig($redisConfig)
    {
        $this->queue = new DefaultQueue($redisConfig);
    }

    /**
     * @return SQManager
     */
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new SQManager();
        }
        return self::$instance;

    }

    /**
     * @param SQIEvent|string $event
     * @param SQIListener|string $listener
     */
    public function register($event, $listener)
    {
        if (is_object($event)) {
            $eventClass = $event::streamName();
        } else {
            $eventClass = call_user_func([$event, 'streamName']);
        }

        $this->eventTable[$eventClass] = @$this->eventTable[$eventClass] ?: [];
        if ($listener instanceof SQIListener) {
            $this->eventTable[$eventClass][] = $listener;
        } else if (is_string($listener)) {
            $this->eventTable[$eventClass][] = new $listener;
        }

    }

    /**
     * @param SQIEvent|SQIJob $object
     */
    private function runNow($object)
    {
        if ($object instanceof SQIEvent) {
            if (!isset($this->eventTable[$object::streamName()]))
                return;
            foreach ($this->getListeners($object::streamName()) as $listener) {
                try {
                    $listener->handle($object);
                } catch (Exception $e) {
                    $this->handleError($e->getMessage());
                }
            }
        } else if ($object instanceof SQIJob) {
            try {
                $object->handle();
            } catch (Exception $e) {
                $this->handleError($e->getMessage());
            }

        }
    }

    /**
     * @return string[]
     */
    public function getEvents()
    {
        return array_keys($this->eventTable);
    }

    /**
     * @param string $eventName
     * @return SQIListener[] |mixed
     */
    public function getListeners($eventName)
    {
        return $this->eventTable[$eventName] ?? [];
    }

    /**
     * @param SQIEvent|SQIJob $object
     * @param array $parameter
     * @throws SQException
     */
    public function dispatch($object, $parameter = [])
    {
        $shouldQueue = isset($parameter['shouldQueue']) ?
            $parameter['shouldQueue']
            : $object->shouldQueue();
        if ($shouldQueue) {
            $this->queueObject($object);
        } else {
            $this->runNow($object);
        }

    }

    /**
     * @param SQIEvent|SQIJob $object
     * @throws SQException
     */
    public function queueObject($object)
    {
        if ($object instanceof SQIEvent) {
            $this->queue->push(self::SQ_MANAGER_PREFIX . $object::streamName(), $object);
        } else if ($object instanceof SQIJob) {
            $this->queue->push(self::SQ_MANAGER_PREFIX . self::SQ_MANAGER_JOB_STREAM, $object);
        }

    }

    /**
     * @param $streamName
     * @param $groupName
     * @param $itemId
     * @param int $count
     * @param bool $newMessage
     * @param int $startId
     * @return SQIJob|SQIEvent|SQIJob[]|SQIEvent[]
     * @throws SQException
     */
    public function loadItem($streamName, $groupName, &$itemId, $count = 1, $newMessage = true, $startId = 0)
    {
        return $this->queue->groupRead(self::SQ_MANAGER_PREFIX . $streamName,
            $groupName,
            $itemId,
            $count,
            $newMessage,
            $startId);

    }

    /**
     * @param string $eventName
     * @param $streamGroupName
     * @param $itemId
     * @throws SQException
     */
    public function ackItem(string $eventName, $streamGroupName, $itemId)
    {
        $this->queue->ack(self::SQ_MANAGER_PREFIX . $eventName, $streamGroupName, $itemId);
    }

    public function getQueue()
    {
        return $this->queue;
    }

    public function destroy()
    {
        $this->queue->destroy();
        $this->eventTable = [];
    }

    public function setErrorHandler($errorHandle)
    {
        $this->errorHandler = $errorHandle;
    }

    public function handleError(string $errorMessage)
    {
        if ($this->errorHandler) {
            ($this->errorHandler)($errorMessage);
        } else {
            Log::error($errorMessage);
        }
    }

}
