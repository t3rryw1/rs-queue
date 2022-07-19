<?php

namespace Laura\Module\Queue\StreamQueue;

use Laura\Lib\Queue\SQManager;
use Laura\Lib\Queue\SQWorker;
use PHPUnit\Framework\TestCase;

/**
 * Class SQManagerTest
 * @package Laura\Module\Queue\StreamQueue
 *
 * Test that:
 * 1. Events are correctly queued
 * 2. Jobs are correctly queued
 * 3. shouldQueue parameter work as expected
 * 4. not queued events are correctly handled.
 */
class SQNewItemWorkerTest extends TestCase
{
    /**
     * @var SQWorker
     */
    private $worker;

    public function setUp(): void
    {
        $this->worker = new SQWorker(true, 0);
        SQManager::load();
        SQManager::getInstance()->register(TestEvent::class, new TestListener());
        SQManager::getInstance()->register(TestEvent::class, new TestStaticListener());
    }

    /**
     * @throws SQException
     */
    public function testBasicEventWorkerShouldQueue()
    {
        (new TestEvent(345))->dispatch(['shouldQueue' => true]);
        (new TestEvent(346))->dispatch(['shouldQueue' => true]);
        (new TestEvent(347))->dispatch(['shouldQueue' => true]);

        $this->worker->singleRun();
        $this->worker->singleRun();
        $this->worker->singleRun();
        $this->assertEquals(TestStaticListener::getValue(), 345 + 346 + 347);
        //no task remains
        $this->worker->singleRun(false, 0);
        $this->assertEquals(TestStaticListener::getValue(), 345 + 346 + 347);
    }

    /**
     * @throws SQException
     */
    public function testBasicJobWorkerShouldQueue()
    {
        (new TestStaticJob(345))->dispatch(['shouldQueue' => true]);
        $this->worker->singleRun();
        $this->assertEquals(TestStaticJob::getStaticValue(), 345);
    }


    public function tearDown(): void
    {
        SQManager::getInstance()->getQueue()->getRedis()->del([
            SQManager::SQ_MANAGER_PREFIX . TestEvent::streamName(),
            SQManager::SQ_MANAGER_PREFIX . SQManager::SQ_MANAGER_JOB_STREAM]);
    }

    public static function tearDownAfterClass(): void
    {
        SQManager::getInstance()->destroy();
    }
}
