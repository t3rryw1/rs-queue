<?php

namespace Laura\Lib\Queue;

use Exception;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

class SQManager
{
    public const SQ_MANAGER_PREFIX = "sqmanager:";
    public const SQ_MANAGER_JOB_STREAM = "jobstream";
    public const SQ_MANAGER_JOB_HANDLER = "jobhandler";

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

    /** @var Logger */
    private $logger;

    public function __construct($logger)
    {
        if(!$logger){
            $this->logger = new Logger('queue');
        }
        $this->logger = $logger;
    }

    public function loadQueueConfig($redisConfig)
    {
        $this->queue = new DefaultQueue($redisConfig);
    }

    public static function load( $useLogger=true, $config=[]){
        if (!self::$instance) {
            if($useLogger){
                $logger = new Logger(isset($config['name'])?$config['name']:'queue');
                $logger->pushHandler(new RotatingFileHandler($config['path']));
                self::$instance = new SQManager($logger);
            }else{
                self::$instance = new SQManager(null);

            }
        }
    }

    /**
     * @return SQManager
     */
    public static function getInstance()
    {
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
        } elseif (is_string($listener)) {
            $this->eventTable[$eventClass][] = new $listener();
        }
    }

    /**
     * @param SQIEvent|SQIJob $object
     */
    private function runNow($object)
    {
        $this->logger->info("Load object without queue - ",(array)$object);

        if ($object instanceof SQIEvent) {
            if (!isset($this->eventTable[$object::streamName()])) {
                return;
            }
            foreach ($this->getListeners($object::streamName()) as $listener) {
                try {
                    $listener->handle($object);
                } catch (Exception $e) {
                    $this->logger->error("Error running sync event - ",['message'=>$e->getMessage()]);
                }
            }
        } elseif ($object instanceof SQIJob) {
            $object->handle();
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
        $this->logger->info("Event dispatched with queue:$shouldQueue - ",(array)$object);
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
        } elseif ($object instanceof SQIJob) {
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
        $item= $this->queue->groupRead(
            self::SQ_MANAGER_PREFIX . $streamName,
            $groupName,
            $itemId,
            $count,
            $newMessage,
            $startId
        );
        $this->logger->info("Load with from queue - ",$item);
        return $item;

    }

    /**
     * @param string $eventName
     * @param $streamGroupName
     * @param $itemId
     */
    public function ackItem(string $eventName, $streamGroupName, $itemId)
    {
        $this->queue->ack(self::SQ_MANAGER_PREFIX . $eventName, $streamGroupName, $itemId);
    }

    /**
     * @return DefaultQueue
     */
    public function getQueue()
    {
        return $this->queue;
    }

    public function destroy()
    {
        $this->getQueue()->destroy();
        $this->eventTable = [];
    }

    public function getLogger(){
        return $this->logger;
    }

}
