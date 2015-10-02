<?php

namespace HSCore;

class Socket {
    private $socket;

    private $secret;

    private $indexes = [];
    private $currentIndex = 1;


    public function __construct($server = 'localhost', $port = 9998, $secret = null) {
        $this->socket = new Driver($server, $port);
        $this->secret = $secret;
    }


    public function __destruct() {
        unset($this->socket, $this->secret);
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
    public function openIndex($dbName, $tableName, $indexName = null, array $columns = [], array $fColumns = null) {
        if (empty($indexName)) {
            $indexName = 'PRIMARY';
        }

        $params = [$dbName, $tableName, $indexName, join(',', $columns)];

        if (!is_null($fColumns)) {
            $params[] = join(',', $fColumns);
        }

        $key = join(';', $params);

        if (!isset($this->indexes[$key])) {
            $this->indexes[$key] = $this->currentIndex++;
            $this->send(array_merge(['P', $this->indexes[$key]], $params));
        }

        return $this->indexes[$key];
    }


    public function getLogs() {
        return $this->socket->getLogs();
    }


    /**
     * Send command to server and parse response
     *
     * @param array $params
     * @return string
     * @throws HSException
     */
    public function request(array $params) {
        return $this->parseResponse($this->send($params));
    }


    /**
     * Send command to server
     *
     * @param array $params
     * @return string
     * @throws HSException
     */
    protected function send(array $params) {
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
    protected function parseResponse($string) {
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
    private function connect() {
        if (!$this->socket->isOpened()) {
            $this->socket->open();

            if ($this->secret) {
                $this->socket->send(join(Driver::SEP, ['A', 1, Driver::encode($this->secret)]).Driver::EOL);
            }
        }
    }
}