<?php

namespace HSCore;

class Socket
{
    private $socket;

    private $secret;

    private static $drivers = [];


    public function __construct($server = 'localhost', $port = 9998, $secret = null)
    {
        $this->socket = self::getDriver($server, $port, $secret);
        $this->secret = $secret;
    }


    /**
     * Perform opening index $indexId over $indexName of table $dbName.$tableName,
     * preparing read $columns and filters $fColumns
     *
     * @param string $dbName
     * @param string $tableName
     * @param string $indexName
     * @param array $columns
     * @param array|null $fColumns
     * @return int
     */
    public function openIndex($dbName, $tableName, $indexName = null, array $columns = [], array $fColumns = null)
    {
        if (empty($indexName)) {
            $indexName = 'PRIMARY';
        }

        $params = [$dbName, $tableName, $indexName, join(',', $columns)];

        if (!is_null($fColumns)) {
            $params[] = join(',', $fColumns);
        }

        $key = join(';', $params);

        return $this->socket->registerIndex(
            $key,
            function ($index) use ($params) {
                $this->send(array_merge(['P', $index], $params));
            }
        );
    }


    /**
     * @return array
     */
    public function getLogs()
    {
        return $this->socket->getLogs();
    }


    /**
     * Send command to server and parse response
     *
     * @see https://github.com/DeNA/HandlerSocket-Plugin-for-MySQL/blob/master/docs-en/protocol.en.txt
     *
     * @param array $params
     * @return array
     * @throws HSException
     */
    public function request(array $params)
    {
        return $this->parseResponse($this->send($params));
    }


    /**
     * Send command to server
     *
     * @param array $params
     * @return string
     * @throws HSException
     */
    protected function send(array $params)
    {
        $this->connect();
        return $this->socket->send(join(Driver::SEP, array_map([$this->socket, 'encode'], $params)).Driver::EOL);
    }


    /**
     * Parse response from server
     *
     * @param $string
     * @return array
     * @throws HSException
     */
    protected function parseResponse($string)
    {
        $exp = explode(Driver::SEP, $string);

        if ($exp[0] != 0) {
            throw new HSException(isset($exp[2]) ? $exp[2] : '', $exp[0]);
        }

        array_shift($exp); // skip error code

        $numCols = intval(array_shift($exp));
        $exp = array_map([$this->socket, 'decode'], $exp);

        return array_chunk($exp, $numCols);
    }


    /**
     * Connect to Handler Socket
     *
     * @throws HSException
     */
    private function connect()
    {
        if (!$this->socket->isOpened()) {
            $this->socket->open();

            if ($this->secret) {
                $this->socket->send(join(Driver::SEP, ['A', 1, Driver::encode($this->secret)]).Driver::EOL);
            }
        }
    }


    /**
     * @param $server
     * @param $port
     * @param $secret
     * @return Driver
     */
    private static function getDriver($server, $port, $secret)
    {
        $id = $server . '|' . $port . '|' . $secret;

        if (!isset(self::$drivers[$id])) {
            self::$drivers[$id] = new Driver($server, $port);
        }

        return self::$drivers[$id];
    }
}
