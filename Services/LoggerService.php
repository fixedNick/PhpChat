<?php

class LoggerService
{
    private $logPath = "log.txt";
    public function Run()
    {
    }
    public function Write($message)
    {
        file_put_contents($this->logPath, "[".date('Y-m-d H:i:s')."] " .$message . PHP_EOL, FILE_APPEND);
    }
}

?>
