<?php

namespace HSLib;

use HSCore\HandlerSocket;

class CacheMultiType implements AdvancedCacheInterface
{
    /**
     * @var HandlerSocket
     */
    private $hs;
    /**
     * @var string
     */
    private $db;
    /**
     * @var string
     */
    private $table;
    /**
     * @var int
     */
    public $manyLimit = 1000;

    /**
     * @param string $readAddress
     * @param string|null $readSecret
     * @param string $writeAddress
     * @param string|null $writeSecret
     * @param string $db
     * @param string $table
     * @param bool $debug
     */
    public function __construct(
        $readAddress, $readSecret,
        $writeAddress, $writeSecret,
        $db, $table,
        $debug = false
    ) {
        $this->hs = new HandlerSocket(
            $readAddress, $readSecret,
            $writeAddress, $writeSecret,
            $debug
        );

        $this->db = $db;
        $this->table = $table;
    }

    /**
     * @inheritdoc
     */
    public function exists($group, $key)
    {
        $params = [
            $this->hs->openReadIndex($this->db, $this->table, null, ['expire']),
            HandlerSocket::OP_EQUAL,
            2,
            $group, $key
        ];

        $res = $this->hs->readRequest($params);

        return !empty($res);
    }

    /**
     * @inheritdoc
     */
    public function valid($group, $key)
    {
        $params = [
            $this->hs->openReadIndex($this->db, $this->table, null, ['expire']),
            HandlerSocket::OP_EQUAL,
            2,
            $group, $key
        ];

        $res = $this->hs->readRequest($params);

        return $res && ($res[0][0] == 0 || $res[0][0] > time());
    }

    /**
     * @inheritdoc
     */
    public function get($group, $key)
    {
        $params = [
            $this->hs->openReadIndex($this->db, $this->table, null, ['expire', 'data']),
            HandlerSocket::OP_EQUAL,
            2,
            $group, $key
        ];

        $res = $this->hs->readRequest($params);

        if ($res && ($res[0][0] == 0 || $res[0][0] > time())) {
            return $res[0][1];
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function getMany($group, array $keys)
    {
        if (empty($keys)) {
            return [];
        }

        $results = [];
        foreach ($keys as $key) {
            $results[$key] = null;
        }

        $ivlen = count($keys);

        $params = array_merge([
            $this->hs->openReadIndex($this->db, $this->table, null, ['key', 'expire', 'data']),
            HandlerSocket::OP_EQUAL,
            2,
            $group, '',
            $ivlen, 0,
            '@', 1, $ivlen
        ], $keys);

        foreach ($this->hs->readRequest($params) as $row) {
            if ($row[1] == 0 || $row[1] > time()) {
                $results[$row[0]] = $row[2];
            }
        }

        return $results;
    }

    /**
     * @inheritdoc
     */
    public function set($group, $key, $value, $duration = 0)
    {
        if (!$this->exists($group, $key)) {
            return $this->add($group, $key, $value, $duration);
        }

        $params = [
            $this->hs->openWriteIndex($this->db, $this->table, null, ['expire', 'data']),
            HandlerSocket::OP_EQUAL,
            2,
            $group, $key,
            1, 0,
            HandlerSocket::COMMAND_UPDATE,
            $duration > 0 ? $duration + time() : 0, $value
        ];

        return (bool)$this->hs->writeRequest($params)[0][0];
    }

    /**
     * @inheritdoc
     */
    public function add($group, $key, $value, $duration = 0)
    {
        try {
            $params = [
                $this->hs->openWriteIndex($this->db, $this->table, null, ['type', 'key', 'expire', 'data']),
                HandlerSocket::COMMAND_INCREMENT,
                4,
                $group, $key, $duration > 0 ? $duration + time() : 0, $value
            ];
            $this->hs->writeRequest($params);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @inheritdoc
     */
    public function delete($group, $key)
    {
        $params = [
            $this->hs->openWriteIndex($this->db, $this->table),
            HandlerSocket::OP_EQUAL,
            2,
            $group, $key,
            1, 0,
            HandlerSocket::COMMAND_DELETE
        ];

        $this->hs->writeRequest($params);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function gc($group)
    {
        $params = [
            $this->hs->openWriteIndex($this->db, $this->table, 'expire', [], ['type', 'expire']),
            HandlerSocket::OP_LESS,
            1,
            time(),
            $this->manyLimit, 0,
            'F', HandlerSocket::OP_EQUAL, 0, $group,
            'F', HandlerSocket::OP_MORE, 1, 0,
            HandlerSocket::COMMAND_DELETE
        ];

        return $this->execMany($params);
    }

    /**
     * @inheritdoc
     */
    public function flush($group)
    {
        $params = [
            $this->hs->openWriteIndex($this->db, $this->table),
            HandlerSocket::OP_EQUAL,
            1,
            $group,
            $this->manyLimit, 0,
            HandlerSocket::COMMAND_DELETE
        ];

        return $this->execMany($params);
    }

    /**
     * @param array $params
     * @return bool
     */
    protected function execMany(array $params)
    {
        do {
            $res = (int)$this->hs->writeRequest($params)[0][0];
        } while ($res == $this->manyLimit);

        return true;
    }
}
