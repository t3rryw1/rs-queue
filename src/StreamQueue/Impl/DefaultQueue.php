<?php


namespace Laura\Module\Queue\StreamQueue\Impl;

use Laura\Module\Queue\StreamQueue\SQException;
use Laura\Module\Queue\StreamQueue\SQIQueue;
use Predis\Client;


class DefaultQueue implements SQIQueue
{
    /**
     * @var Client
     */
    private $redis;

    private $consumerId;

    public function __construct($redisConfig)
    {
        $redisConfig = array_merge([
            'scheme' => 'tcp',
            'host' => '127.0.0.1',
            'port' => 6379
        ], array_filter($redisConfig));
        $this->redis = new Client($redisConfig);

        $this->consumerId = rand(10000, 99999);

    }

    /**
     * @param $name
     * @param $object
     * @return mixed
     * @throws SQException
     */
    public function push($name, $object)
    {
        $error = false;
        $id = $this->redis->executeRaw(["xadd", $name, '*', "data", serialize($object)], $error);
        if (!$error) {
            return $id;
        }
        throw new SQException($error);
    }

    /**
     * @param $key
     * @param int $count
     * @param null $lastId
     * @param int $startingId
     * @return object | object[]
     * @throws SQException
     */
    public function read($key,
                         $count = 1,
                         &$lastId = null,
                         $startingId = 0)
    {
        $error = false;
        $res = $this->redis->executeRaw(["xread",
            'COUNT',
            $count,
            "STREAMS",
            $key,
            $startingId], $error);
        if (!$error) {
            $resultList = [];
            $idList = [];
            $this->getObject($res, $resultList, $idList);
            $lastId = end($idList);
            if (count($resultList) == 1) {
                return reset($resultList);
            } else {
                if ($resultList) {
                    return $resultList;
                }
                return null;
            }
        }
        throw new SQException($error);

    }

    /**
     * @param $name
     * @param $groupName
     * @param $lastId
     * @param int $count
     * @param bool $newMessage
     * @param int $startingId
     * @return object | object[]
     * @throws SQException
     */
    public function groupRead($name,
                              $groupName,
                              &$lastId,
                              $count = 1,
                              $newMessage = true,
                              $startingId = 0)
    {
        $error = false;
        $startingSymbol = $newMessage ? '>' : "$startingId";
        $this->redis->executeRaw(["xgroup",
            "CREATE",
            $name,
            $groupName,
            0,
            "MKSTREAM"], $error);
        //if command complete with error -BUSYGROUP, continue
        $error = false;
        $res = $this->redis->executeRaw(["xreadgroup",
            "GROUP",
            $groupName,
            "$this->consumerId",
            "COUNT",
            "$count",
            "STREAMS",
            $name,
            $startingSymbol], $error);

        if (!$error) {
            $resultList = [];
            $idList = [];
            $this->getObject($res, $resultList, $idList);
            $lastId = end($idList);
            if (count($resultList) == 1) {
                return reset($resultList);
            } else {
                if ($resultList) {
                    return $resultList;
                }
                return null;
            }

        }
        echo "error";
        throw new SQException($error);

    }

    /**
     * @param $name
     * @param $groupName
     * @param $id
     * @return bool
     * @throws SQException
     */
    public function ack($name, $groupName, $id)
    {
        $this->redis->executeRaw(["xack",
            $name,
            $groupName,
            $id], $error);
        if (!$error) {
            return true;
        }
        throw new SQException($error);
    }

    public function destroy()
    {
        $this->redis->quit();
    }

    public function getRedis()
    {
        return $this->redis;
    }

    private function getObject($value, &$resultList, &$idList)
    {
        if (!$value) return null;
        $res = reset($value);
        if (!$res) return null;
        $firstKey = current($res);
        $firstValueSet = next($res);
        if (!$firstValueSet) return null;
        foreach ($firstValueSet as $valueTable) {
            $idList[] = current($valueTable);
            $value = next($valueTable);
            $serialResult = @$value[1];
            if ($serialResult) {
                $resultList [] = unserialize($serialResult);
            }
        }
    }


}
