<?php

namespace Laura\Module\Queue\StreamQueue;

use Laura\Lib\Queue\DefaultQueue;
use PHPUnit\Framework\TestCase;

/**
 * Class StreamQueueTest
 */
class StreamQueueTest extends TestCase
{
    /** @var DefaultQueue */
    public static $queue;

    public static function setUpBeforeClass(): void
    {
        self::$queue = new DefaultQueue([]);
    }

    public function testPushAndRead(): void
    {
        $obj = new TestEvent(123);
        $obj2 = new TestEvent(234);

        //push 2 obj in
        $pushId = self::$queue->push('aaaa', $obj);
        $pushId2 = self::$queue->push('aaaa', $obj2);

        //read first obj 123
        $readId = null;
        $res = self::$queue->read('aaaa', 1, $readId);
        $this->assertInstanceOf(TestEvent::class, $res);

        $this->assertEquals($pushId, $readId);
        $this->assertEquals($res->getValue(), 123);

        //read 2 objs
        $readId = null;
        $res = self::$queue->read('aaaa', 2, $readId);
        $this->assertInstanceOf(TestEvent::class, $res[0]);

        $this->assertEquals($pushId2, $readId);
        $this->assertEquals($res[0]->getValue(), 123);
        $this->assertEquals($res[1]->getValue(), 234);

        //read first obj 123 again
        $res = self::$queue->read('aaaa', 1, $readId);
        $this->assertInstanceOf(TestEvent::class, $res);
        $this->assertEquals($pushId, $readId);
        $this->assertEquals($res->getValue(), 123);

        //read second obj 234
        $lastId = $readId;
        $res = self::$queue->read('aaaa', 1, $readId, $lastId);
        $this->assertInstanceOf(TestEvent::class, $res);
        $this->assertEquals($pushId2, $readId);
        $this->assertEquals($res->getValue(), 234);

        //try to read 3rd obj but fail
        $lastId = $readId;
        $res = self::$queue->read('aaaa', 1, $readId, $lastId);
        $this->assertNull($res);
    }

    public function testPushAndGroupRead(): void
    {
        $obj = new TestEvent(123);
        $obj2 = new TestEvent(234);
        $obj3 = new TestEvent(345);

        //push 2 obj in
        $pushId = self::$queue->push('aaaa', $obj);
        $pushId2 = self::$queue->push('aaaa', $obj2);

        $readId = null;

        //read new obj once
        $res = self::$queue->groupRead('aaaa', 'groupa', $readId);
        $this->assertEquals($pushId, $readId);
        $this->assertInstanceOf(TestEvent::class, $res);

        $this->assertEquals($res->getValue(), 123);
        //read new obj again
        $res = self::$queue->groupRead('aaaa', 'groupa', $readId);
        $this->assertInstanceOf(TestEvent::class, $res);
        $this->assertEquals($pushId2, $readId);
        $this->assertEquals($res->getValue(), 234);

        //read pending obj
        $res = self::$queue->groupRead('aaaa', 'groupa', $readId, 1, false);
        $this->assertInstanceOf(TestEvent::class, $res);
        $this->assertEquals($pushId, $readId);
        $this->assertEquals($res->getValue(), 123);

        //read pending obj from start again
        $res = self::$queue->groupRead('aaaa', 'groupa', $readId, 1, false);
        $this->assertInstanceOf(TestEvent::class, $res);
        $this->assertEquals($pushId, $readId);
        $this->assertEquals($res->getValue(), 123);

        //now ack
        self::$queue->ack('aaaa', 'groupa', $readId);

        //read pending again
        $res = self::$queue->groupRead('aaaa', 'groupa', $readId, 1, false);
        $this->assertInstanceOf(TestEvent::class, $res);
        $this->assertEquals($pushId2, $readId);
        $this->assertEquals($res->getValue(), 234);

        //read new
        $res = self::$queue->groupRead('aaaa', 'groupa', $readId);
        $this->assertNull($res);

        //push one more
        $pushId3 = self::$queue->push('aaaa', $obj3);

        //ack the 2nd obj
        self::$queue->ack('aaaa', 'groupa', $pushId2);

        //read pending should get nothing
        $res = self::$queue->groupRead('aaaa', 'groupa', $readId, 1, false);
        $this->assertNull($res);

        //read new should get obj3
        $res = self::$queue->groupRead('aaaa', 'groupa', $readId);
        $this->assertInstanceOf(TestEvent::class, $res);
        $this->assertEquals($pushId3, $readId);
        $this->assertEquals($res->getValue(), 345);

        //read pending again should get obj3
        $res = self::$queue->groupRead('aaaa', 'groupa', $readId, 1, false);
        $this->assertInstanceOf(TestEvent::class, $res);
        $this->assertEquals($pushId3, $readId);
        $this->assertEquals($res->getValue(), 345);
    }

    public function tearDown(): void
    {
        self::$queue->getRedis()->del(['aaaa']);

        parent::tearDown(); // TODO: Change the autogenerated stub
    }

    public static function tearDownAfterClass(): void
    {
        self::$queue->destroy();
    }
}
