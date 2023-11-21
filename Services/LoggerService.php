<?php

class LoggerService
{
    private $logPath= "log.txt";
    private $server;
    public function __construct($server) {
        $this->server = $server;
    }
    public function Run()
    {
    }
    public function Write($message)
    {
        file_put_contents($this->logPath, "[".date('Y-m-d H:i:s')."] " .$message . PHP_EOL, FILE_APPEND);
    }
}

?>