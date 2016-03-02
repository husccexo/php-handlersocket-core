<?php

namespace HSCore;

class HandlerSocket
{
    // Comparison Operators
    const OP_EQUAL = '=';
    const OP_MORE = '>';
    const OP_MORE_AND = '>=';
    const OP_LESS = '<';
    const OP_LESS_AND = '<=';

    // Commands
    const COMMAND_UPDATE = 'U';
    const COMMAND_DELETE = 'D';
    const COMMAND_INCREMENT = '+';
    const COMMAND_DECREMENT = '-';

    // Filter types
    const FTYPE_FILTER = 'F';
    const FTYPE_WHILE = 'W';


    private $readAddress, $readSecret;
    private $writeAddress, $writeSecret;
    private $debug;

    protected $read, $write;


    public function __construct(
        $readAddress = 'localhost:9998', $readSecret = null,
        $writeAddress = 'localhost:9999', $writeSecret = null,
        $debug = false
    ) {
        $this->readAddress = $readAddress;
        $this->readSecret = $readSecret;

        $this->writeAddress = $writeAddress;
        $this->writeSecret = $writeSecret;

        $this->debug = $debug;
    }


    public function __destruct()
    {
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


    /**
     * @param $dbName
     * @param $tableName
     * @param string $indexName
     * @param array $columns
     * @param array $fColumns
     * @return int
     */
    public function openReadIndex($dbName, $tableName, $indexName = null, array $columns = [], array $fColumns = null)
    {
        return $this->getRead()->openIndex($dbName, $tableName, $indexName, $columns, $fColumns);
    }


    /**
     * @param $dbName
     * @param $tableName
     * @param string $indexName
     * @param array $columns
     * @param array $fColumns
     * @return int
     */
    public function openWriteIndex($dbName, $tableName, $indexName = null, array $columns = [], array $fColumns = null)
    {
        return $this->getWrite()->openIndex($dbName, $tableName, $indexName, $columns, $fColumns);
    }


    /**
     * @param array $params
     * @return array
     */
    public function readRequest(array $params)
    {
        return $this->getRead()->request($params);
    }


    /**
     * @param array $params
     * @return array
     */
    public function writeRequest(array $params)
    {
        return $this->getWrite()->request($params);
    }


    /**
     * @return array
     */
    public function getLogs()
    {
        return [
            'reader' => $this->getRead()->getLogs(),
            'writer' => $this->getWrite()->getLogs()
        ];
    }


    /**
     * Print logs
     */
    public function showLogs()
    {
        $logs = $this->getLogs();

        $result = array_map(function($array) {
            $data = [];
            $time = 0;

            foreach ($array as $row) {
                $data[] = $row['type'].chr(9).number_format($row['time'], 5).chr(9).':'.chr(9).rtrim($row['command']);
                $time += $row['time'];
            }

            return [
                'data' => join(PHP_EOL, $data),
                'totalTime' => number_format($time, 5)
            ];
        }, $logs);

        foreach ($result as $socket => $row) {
            echo PHP_EOL.PHP_EOL.$socket.' (total time: '.$row['totalTime'].')'.PHP_EOL.PHP_EOL.$row['data'].PHP_EOL;
        }
    }


    /**
     * @return Socket
     */
    protected function getRead()
    {
        if (!$this->read) {
            $this->read = new Socket($this->readAddress, $this->readSecret);
        }

        return $this->read;
    }


    /**
     * @return Socket
     */
    protected function getWrite()
    {
        if (!$this->write) {
            $this->write = new Socket($this->writeAddress, $this->writeSecret);
        }

        return $this->write;
    }
}
