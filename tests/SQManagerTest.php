<?php

namespace Laura\Module\Queue\StreamQueue;


use Laura\Module\Queue\StreamQueue\Impl\SQManager;
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
class SQManagerTest extends TestCase
{
    public static function setUpBeforeClass()
    {
        SQManager::getInstance()->loadQueueConfig([]);
    }

    public function setUp()
    {
        SQManager::getInstance()->register(TestEvent::class, new TestListener());
    }

    /**
     * @throws SQException
     */
    public function testBasicListenerNoQueue()
    {
        (new TestEvent(345))->dispatch();
        (new TestEvent(346))->dispatch();
        (new TestEvent(347))->dispatch();

        //no queue means the listener in sq manager handle event in a synchronized fashion, maintain the last value
        $listeners = SQManager::getInstance()->getListeners(TestEvent::streamName());
        foreach ($listeners as $listener) {
            $this->assertEquals($listener->getPlusOne(), 348);
        }
    }

    /**
     * @throws SQException
     */
    public function testBasicListenerShouldQueue()
    {

        //one event, one listener
        (new TestEvent(345))->dispatch(['shouldQueue' => true]);
        $itemId = null;
        $item = SQManager::getInstance()->loadItem(TestEvent::streamName(), (new TestListener())->streamGroupName(), $itemId);
        $listeners = SQManager::getInstance()->getListeners(TestEvent::streamName());
        foreach ($listeners as $listener) {
            $listener->handle($item);
            $this->assertEquals($listener->getPlusOne(), 346);
        }
        (new TestEvent(346))->dispatch(['shouldQueue' => true]);
        $itemId = null;
        $item = SQManager::getInstance()->loadItem(TestEvent::streamName(), (new TestListener())->streamGroupName(), $itemId);
        $listeners = SQManager::getInstance()->getListeners(TestEvent::streamName());
        foreach ($listeners as $listener) {
            $listener->handle($item);
            $this->assertEquals($listener->getPlusOne(), 347);
        }
        (new TestEvent(347))->dispatch(['shouldQueue' => true]);
        $itemId = null;
        $item = SQManager::getInstance()->loadItem(TestEvent::streamName(), (new TestListener())->streamGroupName(), $itemId);
        $listeners = SQManager::getInstance()->getListeners(TestEvent::streamName());
        foreach ($listeners as $listener) {
            $listener->handle($item);
            $this->assertEquals($listener->getPlusOne(), 348);
        }


        //3 events in, 3 listener load
        (new TestEvent(345))->dispatch(['shouldQueue' => true]);
        (new TestEvent(346))->dispatch(['shouldQueue' => true]);
        (new TestEvent(347))->dispatch(['shouldQueue' => true]);
        $itemId = null;
        $item = SQManager::getInstance()->loadItem(TestEvent::streamName(), (new TestListener())->streamGroupName(), $itemId);
        $listeners = SQManager::getInstance()->getListeners(TestEvent::streamName());
        foreach ($listeners as $listener) {
            $listener->handle($item);
            $this->assertEquals($listener->getPlusOne(), 346);
        }

        $item = SQManager::getInstance()->loadItem(TestEvent::streamName(), (new TestListener())->streamGroupName(), $itemId);
        $listeners = SQManager::getInstance()->getListeners(TestEvent::streamName());
        foreach ($listeners as $listener) {
            $listener->handle($item);
            $this->assertEquals($listener->getPlusOne(), 347);
        }
        $item = SQManager::getInstance()->loadItem(TestEvent::streamName(), (new TestListener())->streamGroupName(), $itemId);
        $listeners = SQManager::getInstance()->getListeners(TestEvent::streamName());
        foreach ($listeners as $listener) {
            $listener->handle($item);
            $this->assertEquals($listener->getPlusOne(), 348);
        }
    }

    /**
     * @throws SQException
     */
    public function testJobExecution()
    {
        $newJob = new TestJob(123);
        $newJob->dispatch();
        $this->assertEquals($newJob->getPlusOne(), 125);

        //serialized in queue , original job not effected
        $newJob = new TestJob(123);
        $newJob->dispatch(['shouldQueue' => true]);
        $this->assertEquals($newJob->getPlusOne(), 124);

        //read job from queue and execute
        $itemId = null;
        $item = SQManager::getInstance()->loadItem(SQManager::SQ_MANAGER_JOB_STREAM, SQManager::SQ_MANAGER_JOB_HANDLER, $itemId);
        $item->handle();
        $this->assertEquals($item->getPlusOne(), 125);

    }

    /**
     * @throws SQException
     */
    public function testACK()
    {
        //serialized in queue , original job not effected
        $newJob = new TestJob(123);
        $newJob->dispatch(['shouldQueue' => true]);
        $this->assertEquals($newJob->getPlusOne(), 124);

        //read job from queue and execute
        $itemId = null;
        $item = SQManager::getInstance()->loadItem(SQManager::SQ_MANAGER_JOB_STREAM, SQManager::SQ_MANAGER_JOB_HANDLER, $itemId);
        $item->handle();
        $this->assertEquals($item->getPlusOne(), 125);

        //load more new items return nothing
        $item = SQManager::getInstance()->loadItem(SQManager::SQ_MANAGER_JOB_STREAM, SQManager::SQ_MANAGER_JOB_HANDLER, $itemId);
        $this->assertNull($item);

        //load  pending items return job
        $item = SQManager::getInstance()->loadItem(SQManager::SQ_MANAGER_JOB_STREAM, SQManager::SQ_MANAGER_JOB_HANDLER, $itemId, 1, false);
        $item->handle();
        $this->assertEquals($item->getPlusOne(), 125);

        //on more time should give same job
        $item = SQManager::getInstance()->loadItem(SQManager::SQ_MANAGER_JOB_STREAM, SQManager::SQ_MANAGER_JOB_HANDLER, $itemId, 1, false);
        $item->handle();
        $this->assertEquals($item->getPlusOne(), 125);

        //ack the item

        SQManager::getInstance()->ackItem(SQManager::SQ_MANAGER_JOB_STREAM, SQManager::SQ_MANAGER_JOB_HANDLER, $itemId);

        //load pending items return nothing
        $item = SQManager::getInstance()->loadItem(SQManager::SQ_MANAGER_JOB_STREAM, SQManager::SQ_MANAGER_JOB_HANDLER, $itemId, 1, false);
        $this->assertNull($item);
    }

    public function tearDown()
    {
        SQManager::getInstance()->getQueue()->getRedis()->del([
            SQManager::SQ_MANAGER_PREFIX . TestEvent::streamName(),
            SQManager::SQ_MANAGER_PREFIX . SQManager::SQ_MANAGER_JOB_STREAM]);

    }

    public static function tearDownAfterClass()
    {
        SQManager::getInstance()->destroy();
    }
}
