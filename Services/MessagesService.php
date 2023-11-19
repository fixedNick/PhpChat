<?php
require_once __DIR__ . '\../vendor/autoload.php';

use Workerman\Worker;

class MessagesService extends ServiceBase 
{
    private $socket;
    public function Run()
    {
        global $server;
        echo '[+] MessagesService started' . PHP_EOL;
        echo '[M] Waiting for server Token' . PHP_EOL;
        while($server->services[Services::TOKEN]->GetServerToken() === null)
        {
            sleep(1);
        }
        echo '[M] Server Token received, starting socket' . PHP_EOL;
        $this->socket = new Worker("websocket://127.0.0.1:1234");
        $this->socket->count = 16;
        $this->socket->onMessage = function($connection, $data)
        {
            echo 'NEW MESSAGE: ' . $data . PHP_EOL;
        };

        $this->socket->onClose = function($connection)
        {
            echo 'CLIENT DISCONNECTED';
        };

        Worker::runAll();
    }

    // REQUIRED SERVER TOKEN
    private function OnCommand($connection, $data)
    {

    }

    // REQUIRED CLIENT TOKEN
    private function OnMessage($connection, $data)  
    {

    }
}
?>