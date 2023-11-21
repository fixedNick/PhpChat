<?php

class LoggerService
{
    private $logPath;
    public function Run()
    {
        $this->logPath = "log.txt";
    }
    public function Write($message)
    {
        file_put_contents($this->logPath, "[".date('Y-m-d H:i:s')."] " .$message . PHP_EOL, FILE_APPEND);
    }
}

?>