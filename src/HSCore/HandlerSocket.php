<?php

namespace HSCore;

class HandlerSocket {
    // Comparison Operators
    const OP_EQUAL = '=';
    const OP_MORE = '>';
    const OP_MORE_AND = '>=';
    const OP_LESS = '<';
    const OP_LESS_AND = '<=';

    const COMMAND_UPDATE = 'U';
    const COMMAND_DELETE = 'D';
    const COMMAND_INCREMENT = '+';
    const COMMAND_DECREMENT = '-';

    private $readHost, $readPort, $readSecret;
    private $writeHost, $writePort, $writeSecret;
    private $debug;

    protected $read, $write;


    public function __construct(
        $readHost = 'localhost', $readPort = 9998, $readSecret = null,
        $writeHost = 'localhost', $writePort = 9999, $writeSecret = null,
        $debug = false
    ) {
        $this->readHost = $readHost;
        $this->readPort = $readPort;
        $this->readSecret = $readSecret;

        $this->writeHost = $writeHost;
        $this->writePort = $writePort;
        $this->writeSecret = $writeSecret;

        $this->debug = $debug;
    }


    public function __destruct() {
        if ($this->debug) {
            $this->showLogs();
        }

        if ($this->read) {
            unset($this->read);
        }

        if ($this->write) {
            unset($this->write);
        }
    }


    public function openReadIndex($dbName, $tableName, $indexName = null, array $columns = [], array $fColumns = null) {
        return $this->getRead()->openIndex($dbName, $tableName, $indexName, $columns, $fColumns);
    }


    public function openWriteIndex($dbName, $tableName, $indexName = null, array $columns = [], array $fColumns = null) {
        return $this->getWrite()->openIndex($dbName, $tableName, $indexName, $columns, $fColumns);
    }


    public function readRequest(array $params) {
        return $this->getRead()->request($params);
    }


    public function writeRequest(array $params) {
        return $this->getWrite()->request($params);
    }


    public function getLogs() {
        return [
            'reader' => $this->getRead()->getLogs(),
            'writer' => $this->getWrite()->getLogs()
        ];
    }


    public function showLogs() {
        $logs = $this->getLogs();

        $result = array_map(function($array) {
            $data = [];
            $time = 0;

            foreach ($array as $row) {
                $data[] = $row['type'].chr(9).number_format($row['time'], 5).chr(9).':'.chr(9).rtrim($row['command']);
                $time += $row['time'];
            }

            return [
                'data' => join(chr(10), $data),
                'totalTime' => number_format($time, 5)
            ];
        }, $logs);

        foreach ($result as $socket => $row) {
            echo chr(10).chr(10).$socket.' (total time: '.$row['totalTime'].')'.chr(10).chr(10).$row['data'].chr(10);
        }
    }


    protected function getRead() {
        if (!$this->read) {
            $this->read = new Socket($this->readHost, $this->readPort, $this->readSecret);
        }

        return $this->read;
    }


    protected function getWrite() {
        if (!$this->write) {
            $this->write = new Socket($this->writeHost, $this->writePort, $this->writeSecret);
        }

        return $this->write;
    }
}