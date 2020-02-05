<?php

namespace Laura\Module\Queue\StreamQueue\Impl;


use Laura\Module\Queue\StreamQueue\SQException;
use Laura\Module\Queue\StreamQueue\SQIEvent;
use Laura\Module\Queue\StreamQueue\SQIJob;

class SQWorker
{

    /**
     * @var SQManager
     */
    private $manager;
    private $startId;
    private $newItem;


    public function __construct($newItem = true, $startId = 0, $errorHandle = null)
    {
        $this->manager = SQManager::getInstance();
        if($errorHandle){
            $this->manager->setErrorHandler($errorHandle);
        }
        $this->newItem = $newItem;
        $this->startId = $startId;
    }


    /**
     * @param bool $newItem
     * @param int $startId
     * @throws SQException
     */
    public function singleRun($newItem = true, $startId = 0)
    {
        printf("\nRunning worker\n");


        foreach ($this->manager->getEvents() as $eventName) {
            $listeners = $this->manager->getListeners($eventName);
            foreach ($listeners as $listener) {
                $itemId = null;
                $item = $this->manager->loadItem($eventName,
                    $listener->streamGroupName(),
                    $itemId,
                    1,
                    $newItem,
                    $startId);

                if ($item instanceof SQIEvent) {
                    if ($newItem) {
                        printf("New Event %s received on %s\n", get_class($item), get_class($listener));
                    } else {
                        printf("Pending Event %s received on %s\n", get_class($item), get_class($listener));
                    }
                    try {
                        $res = $listener->handle($item);
                    } catch (\Exception $e) {
                        $this->manager->handleError($e->getMessage());
                        $res = false;
                    }
                    if ($res) {
                        $this->manager->ackItem($eventName,
                            $listener->streamGroupName(),
                            $itemId);
                        printf("Event %s acked on %s\n", get_class($item), get_class($listener));
                    } else {
                        printf("Event %s not handled correctly by %s\n", get_class($item), get_class($listener));
                    }
                }
            }
        }
        $itemId = null;
        $item = $this->manager->loadItem(SQManager::SQ_MANAGER_JOB_STREAM,
            SQManager::SQ_MANAGER_JOB_HANDLER,
            $itemId,
            1,
            $newItem,
            $startId);

        if ($item instanceof SQIJob) {
            if ($newItem) {
                printf("New Job  %s received\n", get_class($item));

            } else {
                printf("Pending Job  %s received\n", get_class($item));
            }
            try {
                $res = $item->handle();
            } catch (\Exception $e) {
                $this->manager->handleError($e->getMessage());
                $res = false;
            }

            if ($res) {
                $this->manager->ackItem(SQManager::SQ_MANAGER_JOB_STREAM,
                    SQManager::SQ_MANAGER_JOB_HANDLER,
                    $itemId);
                printf("Job  %s acked\n", get_class($item));
            } else {
                printf("Job  %s  not handled correctly\n", get_class($item));
            }
        }
        printf("Done worker\n");

    }

    public function run()
    {
        while (true) {
            try {
                $this->singleRun($this->newItem, $this->startId);
            } catch (SQException $e) {
                $this->manager->handleError($e->getMessage());
            }
            sleep(1);
        }

    }
}
