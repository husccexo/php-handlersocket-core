<?php

namespace HSCore;

class Driver
{
    const EOL       = "\n";
    const SEP       = "\t";
    const NULL      = "\0";
    const ESC       = "\1";
    const ESC_SHIFT = 0x40;

    private static $decodeMap = [
        "\x01\x40" => "\x00",
        "\x01\x41" => "\x01",
        "\x01\x42" => "\x02",
        "\x01\x43" => "\x03",
        "\x01\x44" => "\x04",
        "\x01\x45" => "\x05",
        "\x01\x46" => "\x06",
        "\x01\x47" => "\x07",
        "\x01\x48" => "\x08",
        "\x01\x49" => "\x09",
        "\x01\x4A" => "\x0A",
        "\x01\x4B" => "\x0B",
        "\x01\x4C" => "\x0C",
        "\x01\x4D" => "\x0D",
        "\x01\x4E" => "\x0E",
        "\x01\x4F" => "\x0F"
    ];

    private static $encodeMap = [
        "\x00" => "\x01\x40",
        "\x01" => "\x01\x41",
        "\x02" => "\x01\x42",
        "\x03" => "\x01\x43",
        "\x04" => "\x01\x44",
        "\x05" => "\x01\x45",
        "\x06" => "\x01\x46",
        "\x07" => "\x01\x47",
        "\x08" => "\x01\x48",
        "\x09" => "\x01\x49",
        "\x0A" => "\x01\x4A",
        "\x0B" => "\x01\x4B",
        "\x0C" => "\x01\x4C",
        "\x0D" => "\x01\x4D",
        "\x0E" => "\x01\x4E",
        "\x0F" => "\x01\x4F"
    ];

    private $socket;
    private $address;

    private $indexes = [];

    private $logs = [];


    public function __construct($server = 'localhost', $port = 9998)
    {
        $this->address = $server.':'.$port;
    }


    public function __destruct()
    {
        $this->close();
    }


    /**
     * Send string command to server
     *
     * @param string $command
     * @return string
     * @throws HSException
     */
    public function send($command)
    {
        $string = $command;

        $timer = microtime(true);

        while ($string) {
            $bytes = fwrite($this->socket, $string);

            if ($bytes === false) {
                $this->close();
                throw new HSException('Cannot write to socket');
            }

            if ($bytes === 0) {
                return null;
            }

            $string = substr($string, $bytes);
        }

        $this->logs[] = [
            'type' => 'sended',
            'time' => microtime(true) - $timer,
            'command' => $command
        ];

        return $this->receive();
    }


    /**
     * Is opened
     *
     * @return bool
     */
    public function isOpened()
    {
        return is_resource($this->socket);
    }


    /**
     * Encode string for sending to server
     *
     * @param $string
     * @return string
     */
    public static function encode($string)
    {
        return is_null($string) ? self::NULL : strtr($string, self::$encodeMap);
    }


    /**
     * Decode string from server
     *
     * @param $string
     * @return null|string
     */
    public static function decode($string)
    {
        return ($string === self::NULL) ? null : strtr($string, self::$decodeMap);
    }


    /**
     * Open Handler Socket
     *
     * @throwsHSrException
     */
    public function open()
    {
        $this->socket = stream_socket_client('tcp://'.$this->address, $errc, $errs, STREAM_CLIENT_CONNECT);

        if (!$this->socket) {
            throw new HSException('Connection to '.$this->address.' failed');
        }
    }


    /**
     * Close Handler Socket
     */
    public function close()
    {
        if ($this->isOpened()) {
            @fclose($this->socket);
            $this->socket = null;
        }
    }


    /**
     * @param $hash
     * @param $closure
     * @return int
     */
    public function registerIndex($hash, $closure)
    {
        if (!isset($this->indexes[$hash])) {
            $this->indexes[$hash] = count($this->indexes) + 1;
            $closure($this->indexes[$hash]);
        }

        return $this->indexes[$hash];
    }


    /**
     * @return array
     */
    public function getLogs()
    {
        return $this->logs;
    }


    /**
     * Receive one string from server.
     * String haven't trailing \n
     *
     * @return string
     * @throws HSException
     */
    private function receive()
    {
        $timer = microtime(true);

        $str = fgets($this->socket);

        if ($str === false) {
            $this->close();
            throw new HSException('Cannot read from socket');
        }


        $this->logs[] = [
            'type' => 'receive',
            'time' => microtime(true) - $timer,
            'command' => $str
        ];

        return substr($str, 0, -1);
    }
}
